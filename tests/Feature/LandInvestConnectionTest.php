<?php

namespace Tests\Feature;

use App\Integrations\LandInvest\Connection;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\PortalAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LandInvestConnectionTest extends TestCase
{
    use DatabaseTransactions;

    private string $url = 'https://localhost/admin/actions/import-landinvest/connection';

    public function test_connection_controls_require_admin_and_current_password(): void
    {
        $this->post($this->url, ['operation' => 'generate'])->assertRedirect();
        $this->actingAs(new PortalAccount, 'client');
        $this->post($this->url, ['operation' => 'generate'])->assertRedirect();
        $admin = User::factory()->create(['password' => bcrypt('test-password')]);
        $this->actingAs($admin, 'web');
        $this->post($this->url, ['operation' => 'generate', 'password' => 'wrong'])->assertSessionHasErrors('password');
    }

    public function test_generation_rotation_revocation_and_encryption(): void
    {
        config(['cache.default' => 'file']);
        AppSetting::query()->where('key', Connection::SETTING)->delete();
        $admin = User::factory()->create(['password' => bcrypt('test-password')]);
        $this->actingAs($admin, 'web');
        $response = $this->post($this->url, ['operation' => 'generate', 'password' => 'test-password']);
        $response->assertOk();
        $connection = new Connection;
        $first = $connection->state();
        $secret = $first['current']['secret'];
        $this->assertSame(64, strlen($secret));
        $this->assertFalse($first['enabled']);
        $response->assertSee($secret);
        $this->assertStringNotContainsString($secret, AppSetting::valueFor(Connection::SETTING));
        $this->assertStringNotContainsString($secret, json_encode($connection->metadata()));
        $this->assertStringNotContainsString($secret, json_encode(session()->all()));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->post($this->url, ['operation' => 'enable', 'password' => 'test-password'])->assertRedirect();
        $this->assertTrue($connection->state()['enabled']);
        $this->post($this->url, ['operation' => 'rotate', 'overlap' => 1, 'password' => 'test-password'])->assertOk();
        $rotated = $connection->state();
        $this->assertSame($secret, $rotated['previous']['secret']);
        $this->assertNotSame($secret, $rotated['current']['secret']);
        $this->assertGreaterThan(time(), $rotated['previous']['expires']);
        $this->post($this->url, ['operation' => 'revoke', 'password' => 'test-password'])->assertRedirect();
        $this->assertSame(['enabled' => false], $connection->state());
        $audit = AuditLog::query()->where('actor_user_id', $admin->id)->where('event', 'like', 'landinvest.%')->get();
        $this->assertCount(4, $audit);
        $this->assertStringNotContainsString($secret, $audit->toJson());
    }

    public function test_export_key_settings_are_separate_and_disabled_until_enabled(): void
    {
        config(['cache.default' => 'file']);
        $upload = new Connection;
        $upload->save(['enabled' => false]);
        $export = new \App\Integrations\LandInvest\ExportConnection;
        $export->save(['enabled' => false]);
        $this->actingAs(User::factory()->create(['password' => bcrypt('test-password')]), 'web');
        $url = 'https://localhost/admin/actions/export-landinvest/connection';
        $this->post($url, ['operation' => 'generate', 'password' => 'test-password'])->assertOk()
            ->assertSee(route('admin.landinvest.export'));
        $this->assertFalse($export->state()['enabled']);
        $this->assertArrayHasKey('current', $export->state());
        $this->post($url, ['operation' => 'enable', 'password' => 'test-password'])->assertRedirect(route('admin.landinvest.export'));
        $this->assertTrue($export->state()['enabled']);
        $this->assertSame(['enabled' => false], $upload->state());
    }
}
