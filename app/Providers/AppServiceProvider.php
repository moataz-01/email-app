<?php

namespace App\Providers;

use App\Contracts\CredentialCipher;
use App\Contracts\ImapConnectionTester;
use App\Contracts\SmtpConnectionTester;
use App\Services\Credentials\NativeCredentialCipher;
use App\Services\Mail\Imap\ImapProtocolConnectionTester;
use App\Services\Mail\Smtp\EsmtpConnectionTester;
use App\Services\NativeBuild;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;
use Native\Desktop\Builder\Builder;
use Native\Desktop\Drivers\Electron\ElectronServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->bind(CredentialCipher::class, NativeCredentialCipher::class);
        $this->app->bind(ImapConnectionTester::class, ImapProtocolConnectionTester::class);
        $this->app->bind(SmtpConnectionTester::class, EsmtpConnectionTester::class);
        $this->app->bind(Builder::class, fn (): NativeBuild => new NativeBuild(
            buildPath: ElectronServiceProvider::buildPath(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
