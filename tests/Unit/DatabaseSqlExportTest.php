<?php
namespace Tests\Unit;
use App\Services\DatabaseSqlExport;
use PHPUnit\Framework\TestCase;
class DatabaseSqlExportTest extends TestCase
{
    public function test_dump_arguments_use_a_snapshot_without_source_locks_or_shell_interpolation(): void
    {
        $command=DatabaseSqlExport::command('/bin/mariadb-dump','/private/credentials','db;touch malicious',false);
        foreach(['--single-transaction','--quick','--skip-lock-tables','--no-tablespaces','--routines','--events','--triggers'] as $flag)$this->assertContains($flag,$command);
        $this->assertSame('--defaults-file=/private/credentials',$command[1]);
        $this->assertSame(['--','db;touch malicious'],array_slice($command,-2));
        $this->assertNotContains('--flush-logs',$command);
        $this->assertNotContains('--delete-master-logs',$command);
        $this->assertNotContains('--set-gtid-purged=OFF',$command);
        $this->assertContains('--set-gtid-purged=OFF',DatabaseSqlExport::command('/bin/mysqldump','/private/c','db',true));
    }
    public function test_credentials_cannot_inject_option_file_lines(): void
    {
        $quoted=DatabaseSqlExport::optionValue("secret\n[client]\npassword=other\t\r");
        $this->assertStringNotContainsString("\n",$quoted);
        $this->assertStringNotContainsString("\r",$quoted);
        $this->assertStringContainsString('\\n',$quoted);
        $this->assertStringStartsWith('"',$quoted);
        $this->assertStringEndsWith('"',$quoted);
    }
}
