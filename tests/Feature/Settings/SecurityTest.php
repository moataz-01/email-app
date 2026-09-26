<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('appearance preferences are available without application authentication', function () {
    $this->get(route('appearance.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/appearance')->missing('auth'));
    $this->assertGuest();
});

test('removed application security endpoints do not change legacy credentials', function (string $method, string $path) {
    $user = User::factory()->create();
    $this->actingAs($user)->call($method, $path, [
        'current_password' => 'password', 'password' => 'replacement-password',
    ])->assertNotFound();
    expect($user->fresh()->password)->toBe($user->password);
})->with([
    ['GET', '/settings/security'], ['PUT', '/user/password'],
    ['POST', '/user/two-factor-authentication'], ['GET', '/user/confirm-password'],
]);
