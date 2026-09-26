<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->route('home')
            ->width(1440)
            ->height(900)
            ->minWidth(800)
            ->minHeight(600);
    }

    /**
     * Return an array of php.ini directives to be set.
     *
     * @return array<string, string|int|bool>
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
