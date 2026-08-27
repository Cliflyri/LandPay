<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_and_public_registration_is_disabled(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Secure administrator access')
            ->assertSee('Public registration is disabled');

        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_guests_are_redirected_from_the_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_an_administrator_can_log_in(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Strong!Password123'),
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'Strong!Password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_an_administrator_can_choose_to_be_remembered(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Strong!Password123'),
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'Strong!Password123',
            'remember' => '1',
        ]);

        $response->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_an_administrator_can_log_out_all_admin_devices_without_affecting_client_sessions(): void
    {
        $user = User::factory()->create(['remember_token' => 'existing-token']);
        $adminKey = Auth::guard('web')->getName();
        $clientKey = Auth::guard('client')->getName();

        DB::table('sessions')->insert([
            [
                'id' => 'admin-session',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Admin browser',
                'payload' => base64_encode(json_encode([$adminKey => $user->id])),
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'client-session',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Client browser',
                'payload' => base64_encode(json_encode([$clientKey => $user->id])),
                'last_activity' => now()->timestamp,
            ],
        ]);

        $this->actingAs($user)
            ->post(route('admin.settings.security.logout-all'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('web');
        $this->assertDatabaseMissing('sessions', ['id' => 'admin-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'client-session']);
        $this->assertNotSame('existing-token', $user->fresh()->remember_token);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,

            'event' => 'administrator.logged_out_all_devices',
        ]);
    }
    public function test_login_does_not_replay_the_portal_return_delete_route_as_get(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Strong!Password123'),
        ]);

        $this->withSession(['url.intended' => url('/admin/portal-access')])
            ->post(route('admin.login.store'), [
                'email' => $user->email,
                'password' => 'Strong!Password123',
            ])
            ->assertRedirect(route('portal.dashboard', absolute: false));
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $user = User::factory()->create();

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email')
            ->assertSessionDoesntHaveErrors('password');

        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited_by_email_and_ip(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ]);
        }

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_administrator_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.logout'));

        $this->assertGuest();
        $response->assertRedirect(route('admin.login'));
    }
}