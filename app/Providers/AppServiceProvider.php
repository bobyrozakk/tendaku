<?php

namespace App\Providers;

use App\Services\FonnteWhatsAppService;
use App\Services\WhatsAppService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppService::class, FonnteWhatsAppService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerDevCommands();

        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');

        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(120),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }

    /**
     * Jalankan scheduler bersama `composer run dev` supaya pengingat
     * pengembalian terkirim otomatis tanpa terminal tambahan.
     */
    protected function registerDevCommands(): void
    {
        if (! $this->app->runningInConsole() || $this->app->isProduction()) {
            return;
        }

        DevCommands::artisan('schedule:work', 'scheduler');
    }
}
