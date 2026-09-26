<?php

use App\Models\User;

test('removed profile endpoints cannot modify or delete legacy data', function (string $method) {
    $user = User::factory()->create();
    $this->actingAs($user)->call($method, '/settings/profile', ['name' => 'Changed', 'password' => 'password'])->assertNotFound();
    expect($user->fresh()->name)->toBe($user->name);
})->with(['GET', 'PATCH', 'DELETE']);

test('settings opens email account management without login', function () {
    $this->get('/settings')->assertRedirect('/accounts');
    $this->get('/accounts')->assertOk();
    $this->assertGuest();
});
