<?php

namespace Tests\Feature;

use App\Integrations\LandInvest\{Connection, ExportConnection, Export};
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LandInvestExportReceiverTest extends TestCase
{
    private array $state;
    private int $exportCalls = 0;
    private string $csv = "apn,county,contract_status,plan_reference,primary_client_name,property_name\r\n001-A,Test,under_contract,1,Example,Parcel\r\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->state = ['enabled' => true, 'current' => ['id' => 'export-id', 'secret' => 'export-secret']];
        $connection = $this->createStub(ExportConnection::class);
        $connection->method('state')->willReturnCallback(fn () => $this->state);
        $connection->method('save')->willReturnCallback(function ($state) { $this->state = $state; });
        $connection->method('cache')->willReturn(Cache::store('array'));
        $this->app->instance(ExportConnection::class, $connection);
        $upload = $this->createStub(Connection::class);
        $upload->method('state')->willReturn(['enabled' => true, 'current' => ['id' => 'upload-id', 'secret' => 'upload-secret']]);
        $upload->method('cache')->willReturn(Cache::store('array'));
        $this->app->instance(Connection::class, $upload);
        $export = $this->createStub(Export::class);
        $export->method('snapshot')->willReturnCallback(function () {
            $this->exportCalls++;
            return ['csv' => $this->csv, 'planCount' => 1, 'missingApns' => 0];
        });
        $this->app->instance(Export::class, $export);
    }

    private function headers(): array
    {
        $time = (string) time();
        $nonce = bin2hex(random_bytes(16));
        return [
            'HTTP_X_LANDINVEST_KEY' => 'export-id', 'HTTP_X_LANDINVEST_TIMESTAMP' => $time,
            'HTTP_X_LANDINVEST_NONCE' => $nonce,
            'HTTP_X_LANDINVEST_SIGNATURE' => ExportConnection::signature('export-secret', 'export-id', $time, $nonce),
        ];
    }

    private function fetch(array $headers = [], string $url = 'https://localhost/webhooks/landinvest/export')
    {
        return $this->call('GET', $url, [], [], [], $headers ?: $this->headers());
    }

    public function test_complete_csv_headers_and_replay_protection(): void
    {
        $headers = $this->headers();
        $result = $this->fetch($headers);
        $result->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertHeader('X-LandPay-Plan-Count', '1');
        $this->assertSame($this->csv, $result->getContent());
        $this->assertStringContainsString('no-store', $result->headers->get('Cache-Control'));
        $this->assertFalse($result->headers->has('Set-Cookie'));
        $this->assertNotEmpty($this->state['last_received_at']);
        $this->fetch($headers)->assertStatus(409);
        $this->assertSame(1, $this->exportCalls);
    }

    public function test_invalid_expired_disabled_and_insecure_requests_do_not_export(): void
    {
        $headers = $this->headers();
        $headers['HTTP_X_LANDINVEST_SIGNATURE'] = str_repeat('0', 64);
        $this->fetch($headers)->assertUnauthorized();
        $headers = $this->headers();
        $headers['HTTP_X_LANDINVEST_TIMESTAMP'] = (string) (time() - 900);
        $this->fetch($headers)->assertUnauthorized();
        $this->fetch([], 'http://localhost/webhooks/landinvest/export')->assertStatus(400);
        $this->fetch([], 'https://localhost/webhooks/landinvest/export?all=1')->assertStatus(400);
        $this->state['enabled'] = false;
        $this->fetch()->assertStatus(503);
        $this->assertSame(0, $this->exportCalls);
    }

    public function test_credentials_are_isolated_in_both_directions(): void
    {
        $headers = $this->headers();
        $headers['HTTP_X_LANDINVEST_KEY'] = 'upload-id';
        $headers['HTTP_X_LANDINVEST_SIGNATURE'] = ExportConnection::signature('upload-secret', 'upload-id', $headers['HTTP_X_LANDINVEST_TIMESTAMP'], $headers['HTTP_X_LANDINVEST_NONCE']);
        $this->fetch($headers)->assertUnauthorized();
        $headers = $this->headers();
        $body = '{}';
        $headers['CONTENT_TYPE'] = 'application/json';
        $headers['HTTP_X_LANDINVEST_SIGNATURE'] = Connection::signature('export-secret', 'export-id', $headers['HTTP_X_LANDINVEST_TIMESTAMP'], $headers['HTTP_X_LANDINVEST_NONCE'], $body);
        $this->call('POST', 'https://localhost/webhooks/landinvest/repayments', [], [], [], $headers, $body)->assertUnauthorized();
        $this->assertSame(0, $this->exportCalls);
    }
}
