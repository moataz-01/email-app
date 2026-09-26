<?php

use App\Models\User;

test('application authentication endpoints are unavailable', function (string $method, string $path) {
    $this->call($method, $path)->assertNotFound();
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with([
    ['GET', '/login'], ['POST', '/login'], ['GET', '/register'], ['POST', '/register'],
    ['POST', '/logout'], ['GET', '/two-factor-challenge'], ['POST', '/two-factor-challenge'],
]);

test('legacy application credentials cannot create a login session', function () {
    $user = User::factory()->create();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertNotFound();
    $this->assertGuest();
    expect($user->fresh())->not->toBeNull();
});
