<?php

namespace Tests\Unit;

use App\Integrations\LandInvest\RepaymentSnapshot;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LandInvestRepaymentSnapshotTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir().'/landpay-repayment-'.bin2hex(random_bytes(8)).'.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    private function payload(array $properties): string
    {
        return json_encode(['schema_version' => 1, 'generated_at' => '2026-11-05T20:17:31Z', 'properties' => $properties]);
    }

    public function test_status_lookup_normalization_and_complete_replacement(): void
    {
        $store = new RepaymentSnapshot($this->path);
        $this->assertNull($store->status('001-02-003A'));
        $store->import($this->payload([
            ['plan_number' => '001-02-003A', 'status' => 'paid'],
            ['plan_number' => '001-02-003B', 'status' => 'partial'],
            ['plan_number' => 'RGRRU1S5L896', 'status' => 'unpaid'],
            ['plan_number' => '208-24-171', 'status' => 'not_funded'],
        ]));
        $this->assertSame('paid', $store->status('001-02-003a (1)'));
        $this->assertSame('partial', $store->status('001-02-003B (#)'));
        $this->assertSame('unpaid', $store->status('RGRRU1S5L896'));
        $this->assertSame('not_funded', $store->status('208-24-171'));
        $this->assertNull($store->status('missing'));
        $this->assertNotEmpty($store->current()['imported_at']);
        $store->import($this->payload([['plan_number' => '208-24-171', 'status' => 'paid']]));
        $this->assertNull($store->status('001-02-003A'));
        $this->assertSame('paid', (new RepaymentSnapshot($this->path))->status('208-24-171'));
        $store->import($this->payload([]));
        $this->assertNull($store->status('208-24-171'));
    }

    public function test_invalid_imports_preserve_previous_snapshot(): void
    {
        $store = new RepaymentSnapshot($this->path);
        $store->import($this->payload([['plan_number' => '001-A', 'status' => 'paid']]));
        $before = file_get_contents($this->path);
        foreach ([
            '{bad json',
            str_replace('"schema_version":1', '"schema_version":2', $this->payload([])),
            str_replace('2026-11-05', '2026-02-31', $this->payload([])),
            $this->payload([['plan_number' => '001-A', 'status' => 'unknown']]),
            $this->payload([['plan_number' => '(1)', 'status' => 'paid']]),
            $this->payload([['plan_number' => '001-A', 'status' => 'paid'], ['plan_number' => '001-A (2)', 'status' => 'unpaid']]),
        ] as $invalid) {
            try {
                $store->import($invalid);
                $this->fail('Invalid snapshot accepted.');
            } catch (InvalidArgumentException) {
                $this->assertSame($before, file_get_contents($this->path));
            }
        }
    }
}
