<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CreateAdminUser extends Command
{
    protected $signature = 'landpay:create-admin {--name=} {--email=}';

    protected $description = 'Create a LandPay administrator account without enabling public registration';

    public function handle(): int
    {
        if (! Schema::hasTable('users')) {
            $this->components->error('The users table does not exist. Run the approved Laravel migrations first.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Administrator name')));
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Administrator email'))));
        $password = (string) $this->secret('Password (minimum 12 characters with mixed case, a number, and a symbol)');
        $confirmation = (string) $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->components->error('The password confirmation does not match.');

            return self::FAILURE;
        }

        try {
            validator(
                compact('name', 'email', 'password'),
                [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
                ],
            )->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->components->error($message);
                }
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
        ]);

        $this->components->info("Administrator {$email} created.");

        return self::SUCCESS;
    }
}