<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('application password recovery is unavailable and sends no notifications', function (string $method, string $path) {
    Notification::fake();
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->call($method, $path, ['email' => $user->email, 'password' => 'replacement-password'])->assertNotFound();

    Notification::assertNothingSent();
    expect($user->fresh()->password)->toBe($originalPassword);
})->with([
    ['GET', '/forgot-password'], ['POST', '/forgot-password'],
    ['GET', '/reset-password/unused-token'], ['POST', '/reset-password'],
]);
