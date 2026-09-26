<?php

namespace App\Http\Controllers;

use App\Models\MailAccount;
use Illuminate\Http\RedirectResponse;

class LaunchController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return to_route(MailAccount::query()->exists() ? 'dashboard' : 'accounts.create');
    }
}
