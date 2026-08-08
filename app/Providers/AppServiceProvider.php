<?php

namespace App\Providers;

use App\Services\SmtpConfigurationService;
use App\Models\PortalAccount;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(SmtpConfigurationService::class)->apply();
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return $notifiable instanceof PortalAccount
                ? route('portal.password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()])
                : url('/reset-password/'.$token.'?email='.urlencode($notifiable->getEmailForPasswordReset()));
        });
    }
}
