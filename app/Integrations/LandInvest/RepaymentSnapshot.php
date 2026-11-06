<?php

namespace App\Integrations\LandInvest;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

class RepaymentSnapshot
{
    public const STATUSES = ['paid', 'partial', 'unpaid', 'not_funded'];
    private ?array $snapshot = null;

    public function __construct(private ?string $path = null)
    {
        $this->path ??= storage_path('app/private/landinvest/repayment-status.json');
    }

    public static function normalize(?string $value): string
    {
        // Match the property-tax importer, also accepting the literal admin marker (#).
        $value = preg_replace('/\s*\((?:\d+|#)\)\s*$/', '', (string) $value);
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $value));
    }

    public function parse(string $json): array
    {
        try {
            $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('Paste a valid JSON snapshot from LandInvest.');
        }
        if (! is_array($data) || ($data['schema_version'] ?? null) !== 1
            || ! is_string($data['generated_at'] ?? null)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $data['generated_at'])
            || ! is_array($data['properties'] ?? null) || ! array_is_list($data['properties'])) {
            throw new InvalidArgumentException('Expected schema version 1, a UTC generated_at timestamp, and a properties list.');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $data['generated_at'], new \DateTimeZone('UTC'));
        if (! $date || $date->format('Y-m-d\TH:i:s\Z') !== $data['generated_at']) {
            throw new InvalidArgumentException('The export timestamp is invalid.');
        }
        $properties = [];
        foreach ($data['properties'] as $index => $property) {
            if (! is_array($property) || ! is_string($property['plan_number'] ?? null)
                || strlen($property['plan_number']) > 255
                || ! in_array($property['status'] ?? null, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid plan number or status at property '.($index + 1).'.');
            }
            $key = self::normalize($property['plan_number']);
            if ($key === '' || isset($properties[$key])) {
                throw new InvalidArgumentException('Empty or duplicate normalized plan number at property '.($index + 1).'.');
            }
            $properties[$key] = ['plan_number' => trim($property['plan_number']), 'status' => $property['status']];
        }
        return ['schema_version' => 1, 'generated_at' => $data['generated_at'], 'properties' => array_values($properties)];
    }

    public function import(string $json, string $source = 'manual'): array
    {
        $data = $this->parse($json);
        $files = new Filesystem;
        $files->ensureDirectoryExists(dirname($this->path), 0700);
        $lock = fopen($this->path.'.lock', 'c');
        if (! $lock) {
            throw new \RuntimeException('Cannot lock snapshot.');
        }
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Cannot lock snapshot.');
            }
            $this->snapshot = null;
            $current = $this->current();
            if ($source === 'https') {
                if (empty($data['properties'])) {
                    throw new InvalidArgumentException('Automated snapshots cannot be empty.');
                }
                if (strtotime($data['generated_at']) > time() + 300) {
                    throw new InvalidArgumentException('Export timestamp is in the future.');
                }
                if ($current && $data['generated_at'] < $current['generated_at']) {
                    throw new InvalidArgumentException('Snapshot is older than the stored export.');
                }
                if ($current && $data['generated_at'] === $current['generated_at']) {
                    if ($data['properties'] === $current['properties']) {
                        return $current;
                    }
                    throw new InvalidArgumentException('Conflicting snapshot with the same export timestamp.');
                }
            }
            $data['imported_at'] = gmdate('Y-m-d\TH:i:s\Z');
            $data['source'] = $source;
            $files->replace($this->path, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n", 0600);
            $this->snapshot = null;
            return $data;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function current(): array
    {
        if ($this->snapshot !== null) {
            return $this->snapshot;
        }
        if (! is_file($this->path)) {
            return $this->snapshot = [];
        }
        $raw = file_get_contents($this->path);
        $data = $this->parse($raw);
        $metadata = json_decode($raw, true);
        $data['imported_at'] = $metadata['imported_at'] ?? null;
        $data['source'] = $metadata['source'] ?? 'manual';
        $data['lookup'] = [];
        foreach ($data['properties'] as $property) {
            $data['lookup'][self::normalize($property['plan_number'])] = $property['status'];
        }
        return $this->snapshot = $data;
    }

    public function status(?string $planNumber): ?string
    {
        return $this->current()['lookup'][self::normalize($planNumber)] ?? null;
    }
}
