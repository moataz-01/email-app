<?php

namespace Database\Factories;

use App\Models\MailAccount;
use App\Models\Mailbox;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mailbox> */
class MailboxFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mail_account_id' => MailAccount::factory(),
            'provider_id' => fake()->uuid(),
            'name' => 'Inbox',
            'kind' => 'inbox',
        ];
    }
}
