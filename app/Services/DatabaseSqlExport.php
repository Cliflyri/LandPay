<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseSqlExport
{
    public static function optionValue(string $value): string
    {
        return '"'.strtr($value, ["\\"=>"\\\\", '"'=>'\\"', "\n"=>'\\n', "\r"=>'\\r', "\t"=>'\\t']).'"';
    }

    public static function command(string $binary, string $credentials, string $database, bool $mysql): array
    {
        $command = [$binary, '--defaults-file='.$credentials, '--single-transaction', '--quick',
            '--skip-lock-tables', '--skip-add-locks', '--no-tablespaces', '--hex-blob',
            '--default-character-set=utf8mb4', '--routines', '--events', '--triggers'];
        if ($mysql) $command[] = '--set-gtid-purged=OFF';
        return [...$command, '--', $database];
    }

    /** Generate completely before downloading; the returned temporary stream belongs to the caller. */
    public function create()
    {
        $connection = DB::connection();
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('SQL downloads currently support MySQL and MariaDB databases only.');
        }
        $config = $connection->getConfig();
        $database = $connection->getDatabaseName();
        $tables = $connection->select("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$database]);
        if (! $tables || collect($tables)->contains(fn ($table) => strcasecmp((string) $table->ENGINE, 'InnoDB') !== 0)) {
            throw new RuntimeException('Export requires a non-empty database with only InnoDB base tables. No backup was created.');
        }
        if (! function_exists('proc_open')) throw new RuntimeException('The hosting PHP configuration does not allow database exports.');
        $binary = null;
        foreach (['/bin/mariadb-dump', '/usr/bin/mariadb-dump', '/bin/mysqldump', '/usr/bin/mysqldump'] as $candidate) {
            if (is_executable($candidate)) { $binary = $candidate; break; }
        }
        if (! $binary) throw new RuntimeException('No supported database dump utility was found on this server.');
        $directory = storage_path('app/private/database-export');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Private export storage is unavailable.');
        }
        $lock = fopen($directory.'/export.lock', 'c');
        if (! $lock) throw new RuntimeException('Cannot open the export lock.');
        chmod($directory.'/export.lock', 0600);
        if (! flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); throw new RuntimeException('Another database export is running. Please try again shortly.'); }
        $credentials = null; $output = null;
        try {
            $credentials = tempnam(sys_get_temp_dir(), 'landpay-db-');
            if ($credentials === false || ! chmod($credentials, 0600)) throw new RuntimeException('Cannot create private export credentials.');
            clearstatcache(true, $credentials);
            if ((fileperms($credentials) & 0077) !== 0) throw new RuntimeException('The server cannot protect temporary export credentials. Export refused.');
            $options = ['user'=>(string) $config['username'], 'password'=>(string) ($config['password'] ?? '')];
            if (! empty($config['unix_socket'])) {
                $options['socket'] = $config['unix_socket'];
            } else {
                $host = $config['host'] ?? '127.0.0.1';
                $options['host'] = is_array($host) ? reset($host) : $host;
                $options['port'] = (string) ($config['port'] ?? 3306);
            }
            $ca = $config['options'][\PDO::MYSQL_ATTR_SSL_CA] ?? null;
            if ($ca) $options['ssl-ca'] = $ca;
            $contents = "[client]\n";
            foreach ($options as $key=>$value) $contents .= $key.'='.self::optionValue((string) $value)."\n";
            if (file_put_contents($credentials, $contents) === false) throw new RuntimeException('Cannot write private export credentials.');
            $version = new Process([$binary, '--no-defaults', '--version']);
            $version->setTimeout(5); $version->run();
            if (! $version->isSuccessful()) throw new RuntimeException('Cannot identify the database dump utility.');
            $mysql = stripos($version->getOutput(), 'mariadb') === false;
            $output = tmpfile();
            if (! $output) throw new RuntimeException('Cannot create a temporary SQL download.');
            $process = new Process(self::command($binary, $credentials, $database, $mysql));
            $process->setTimeout(40);
            try {
                $process->run(function ($type, $buffer) use ($output, $process): void {
                    if ($type === Process::OUT && fwrite($output, $buffer) !== strlen($buffer)) throw new RuntimeException('Cannot write SQL export.');
                    // Do not retain SQL or credentials in process diagnostic buffers.
                    $process->clearOutput(); $process->clearErrorOutput();
                });
            } catch (\Throwable $exception) {
                $process->stop(0);
                throw new RuntimeException('Database export could not finish within the server limits. No backup was downloaded.');
            }
            if (! $process->isSuccessful() || ftell($output) === 0) {
                throw new RuntimeException('Database export failed. Check dump permissions and hosting configuration. No backup was downloaded.');
            }
            rewind($output);
            return $output;
        } catch (\Throwable $exception) {
            if (is_resource($output)) fclose($output);
            throw $exception;
        } finally {
            if ($credentials && is_file($credentials)) unlink($credentials);
            flock($lock, LOCK_UN); fclose($lock);
        }
    }
}
