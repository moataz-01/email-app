<?php

use App\Models\MailAccount;

test('demo mail is created without a user and repeated runs are harmless', function () {
    $existing = MailAccount::factory()->create();
    $this->artisan('mail:demo')->assertSuccessful();
    $this->artisan('mail:demo')->assertSuccessful();

    $this->assertDatabaseCount('mail_accounts', 3);
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('mail_messages', 8);
    $this->assertDatabaseCount('mail_message_bodies', 8);
    expect($existing->fresh()->email)->toBe($existing->email);
});

test('default seeding does not create application users', function () {
    $this->seed();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('mail_accounts', 0);
});
