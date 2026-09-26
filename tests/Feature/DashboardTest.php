<?php

use App\Models\MailAccount;

test('local inbox is available without login', function () {
    $this->get(route('dashboard'))->assertOk();
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('configured accounts open directly into the inbox', function () {
    MailAccount::factory()->create();
    $this->get(route('home'))->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertOk();
});
