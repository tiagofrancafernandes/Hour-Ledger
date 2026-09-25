<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\PaymentMethods\BankTransferPaymentMethod;
use App\PaymentMethods\PaymentMethodRegistry;
use App\PaymentMethods\PixOfflinePaymentMethod;
use App\PaymentMethods\PixPaymentMethod;
use App\Services\TenantResolver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantResolver::class, function () {
            $environment = config('app.environment') ?? 'prod';

            return new TenantResolver($environment);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => config('app.frontend_url') . "/password-reset/{$token}?email={$notifiable->getEmailForPasswordReset()}");

        // Register payment methods
        PaymentMethodRegistry::register(PixOfflinePaymentMethod::class);
        PaymentMethodRegistry::register(PixPaymentMethod::class);
        PaymentMethodRegistry::register(BankTransferPaymentMethod::class);
    }
}
