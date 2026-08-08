<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_be_created_from_the_console(): void
    {
        $this->artisan('landpay:create-admin', [
            '--name' => 'LandPay Administrator',
            '--email' => 'admin@example.com',
        ])
            ->expectsQuestion('Password (minimum 12 characters with mixed case, a number, and a symbol)', 'Strong!Password123')
            ->expectsQuestion('Confirm password', 'Strong!Password123')
            ->expectsOutputToContain('Administrator admin@example.com created.')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('LandPay Administrator', $admin->name);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertNotSame('Strong!Password123', $admin->password);
    }

    public function test_the_command_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('landpay:create-admin', [
            '--name' => 'Another Administrator',
            '--email' => 'admin@example.com',
        ])
            ->expectsQuestion('Password (minimum 12 characters with mixed case, a number, and a symbol)', 'Strong!Password123')
            ->expectsQuestion('Confirm password', 'Strong!Password123')
            ->expectsOutputToContain('The email has already been taken.')
            ->assertFailed();
    }
}