<?php

namespace Tests\Feature;

use App\Integrations\LandInvest\Connection;
use App\Integrations\LandInvest\RepaymentSnapshot;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LandInvestReceiverTest extends TestCase
{
    private string $path;
    private string $secret = 'test-only-secret';
    private array $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = sys_get_temp_dir().'/landinvest-test-'.bin2hex(random_bytes(8)).'.json';
        $this->state = ['enabled' => true, 'current' => ['id' => 'test-key', 'secret' => $this->secret]];
        $connection = $this->createStub(Connection::class);
        $connection->method('state')->willReturnCallback(fn () => $this->state);
        $connection->method('save')->willReturnCallback(function ($state) { $this->state = $state; });
        $connection->method('cache')->willReturn(Cache::store('array'));
        $this->app->instance(Connection::class, $connection);
        $this->app->instance(RepaymentSnapshot::class, new RepaymentSnapshot($this->path));
    }

    protected function tearDown(): void
    {
        foreach ([$this->path, $this->path.'.lock'] as $path) {
            if (is_file($path)) { unlink($path); }
        }
        parent::tearDown();
    }

    private function body(int $offset = 0, array $properties = [['plan_number' => '123-A', 'status' => 'paid']]): string
    {
        return json_encode(['schema_version' => 1, 'generated_at' => gmdate('Y-m-d\TH:i:s\Z', time() + $offset), 'properties' => $properties]);
    }

    private function send(string $body, array $overrides = [], bool $secure = true)
    {
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $headers = [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_LANDINVEST_KEY' => 'test-key', 'HTTP_X_LANDINVEST_TIMESTAMP' => $timestamp,
            'HTTP_X_LANDINVEST_NONCE' => $nonce,
            'HTTP_X_LANDINVEST_SIGNATURE' => Connection::signature($this->secret, 'test-key', $timestamp, $nonce, $body),
        ];
        return $this->call('POST', ($secure ? 'https' : 'http').'://localhost/webhooks/landinvest/repayments', [], [], [], array_replace($headers, $overrides), $body);
    }

    public function test_valid_upload_idempotence_and_source(): void
    {
        $body = $this->body();
        $this->send($body)->assertOk()->assertJson(['ok' => true, 'property_count' => 1]);
        $original = file_get_contents($this->path);
        $this->assertSame('https', json_decode($original, true)['source']);
        $this->send($body)->assertOk();
        $this->assertSame($original, file_get_contents($this->path));
        $this->assertNotEmpty($this->state['last_received_at']);
    }

    public function test_signature_replay_expiry_disabled_and_transport(): void
    {
        $body = $this->body();
        $this->send($body, ['HTTP_X_LANDINVEST_SIGNATURE' => str_repeat('0', 64)])->assertUnauthorized();
        $this->send($body, ['HTTP_X_LANDINVEST_TIMESTAMP' => (string) (time() - 1000)])->assertUnauthorized();
        $this->send($body, [], false)->assertStatus(400);
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string) time();
        $headers = ['HTTP_X_LANDINVEST_NONCE' => $nonce, 'HTTP_X_LANDINVEST_TIMESTAMP' => $timestamp,
            'HTTP_X_LANDINVEST_SIGNATURE' => Connection::signature($this->secret, 'test-key', $timestamp, $nonce, $body)];
        $this->send($body, $headers)->assertOk();
        $this->send($body, $headers)->assertStatus(409);
        $this->state['enabled'] = false;
        $this->send($body)->assertStatus(503);
    }

    public function test_rejected_snapshots_preserve_file(): void
    {
        $this->send($this->body())->assertOk();
        $before = file_get_contents($this->path);
        foreach ([$this->body(-60), $this->body(0, []), '{bad', $this->body(3600),
            $this->body(0, [['plan_number' => '123-A', 'status' => 'partial']])] as $body) {
            $this->send($body)->assertStatus(422);
            $this->assertSame($before, file_get_contents($this->path));
        }
        $this->send(str_repeat('x', 2097153))->assertStatus(413);
    }

    public function test_expired_previous_key_and_unavailable_cache_fail_closed(): void
    {
        $this->state['previous'] = $this->state['current'] + ['expires' => time() - 1];
        $this->state['current']['id'] = 'replacement';
        $this->send($this->body())->assertUnauthorized();
        $this->state['previous']['expires'] = time() + 60;
        $this->send($this->body())->assertOk();
        $connection = $this->createStub(Connection::class);
        $connection->method('cache')->willThrowException(new \RuntimeException('Unavailable'));
        $this->app->instance(Connection::class, $connection);
        $this->send($this->body())->assertStatus(503);
    }
}
