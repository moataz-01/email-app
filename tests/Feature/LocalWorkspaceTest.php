<?php

use App\Models\MailAccount;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Inertia\Testing\AssertableInertia as Assert;
use Native\Desktop\Http\Middleware\PreventRegularBrowserAccess;

beforeEach(function () {
    $this->withoutVite();
});

test('first launch opens email setup without creating or authenticating an application user', function () {
    $this->get('/')->assertRedirect('/accounts/create');
    $this->get('/accounts/create')->assertOk()->assertInertia(fn (Assert $page) => $page->component('accounts/create')->missing('auth'));
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('returning installations open the local inbox without a session', function () {
    MailAccount::factory()->create();
    $this->get('/')->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->has('accounts', 1)->missing('auth'));
    $this->assertGuest();
});

test('account configuration no longer needs a user record', function () {
    $account = MailAccount::factory()->create();
    $this->assertDatabaseCount('users', 0);
    expect($account->legacy_user_id)->toBeNull();
});

test('old application sessions do not restrict local mail', function () {
    MailAccount::factory()->count(2)->create();
    $this->actingAs(User::factory()->create())->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->has('accounts', 2)->missing('auth'));
});

test('remote and rebinding requests cannot access the local workspace', function (string $ip, string $host) {
    $this->withServerVariables(['REMOTE_ADDR' => $ip])->withHeader('Host', $host)
        ->get('http://'.$host.'/accounts/create')->assertForbidden();
})->with([
    'LAN client' => ['192.168.1.20', 'localhost'],
    'untrusted hostname' => ['127.0.0.1', 'attacker.test'],
]);

test('native runtime rejects access without its process secret', function () {
    config(['nativephp-internal.running' => true, 'nativephp-internal.secret' => 'test-native-process-secret']);
    $this->app->make(Kernel::class)->pushMiddleware(PreventRegularBrowserAccess::class);
    $this->get('/dashboard')->assertForbidden();
    $this->withHeader('X-NativePHP-Secret', 'wrong-secret')->get('/dashboard')->assertForbidden();
    $this->withHeader('X-NativePHP-Secret', 'test-native-process-secret')->get('/dashboard')->assertOk();
});

test('account creation still requires CSRF protection without application login', function () {
    $this->app['env'] = 'local';
    try {
        $this->post('/accounts', [])->assertStatus(419);
        $this->assertDatabaseCount('mail_accounts', 0);
    } finally {
        $this->app['env'] = 'testing';
    }
});
