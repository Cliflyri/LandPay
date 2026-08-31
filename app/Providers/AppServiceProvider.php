<?php

namespace App\Providers;

use App\Services\SmtpConfigurationService;
use App\Models\AdminNotice;
use App\Models\PortalAccount;
use App\Models\SecureMessageThread;
use App\Observers\AdminNoticeObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\View;
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
        AdminNotice::observe(AdminNoticeObserver::class);
        View::composer('layouts.admin', fn ($view) => $view->with([
            'openAdminNoticeCount' => AdminNotice::query()->whereNull('dismissed_at')->count(),
            'unreadSecureMessageCount' => SecureMessageThread::query()->unreadByAdmin()->count(),
            'starredSecureMessageCount' => SecureMessageThread::query()->whereNotNull('starred_at')->count(),
        ]));
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return $notifiable instanceof PortalAccount
                ? route('portal.password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()])
                : url('/reset-password/'.$token.'?email='.urlencode($notifiable->getEmailForPasswordReset()));
        });
    }
}
