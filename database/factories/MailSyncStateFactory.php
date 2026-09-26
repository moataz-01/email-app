<?php

namespace Database\Factories;

use App\Models\MailAccount;
use App\Models\MailSyncState;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailSyncState> */
class MailSyncStateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mail_account_id' => MailAccount::factory(),
            'scope' => 'account',
        ];
    }
}
