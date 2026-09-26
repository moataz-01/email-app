<?php

namespace Database\Factories;

use App\Models\MailAccount;
use App\Models\MailThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailThread> */
class MailThreadFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mail_account_id' => MailAccount::factory(),
            'provider_id' => fake()->uuid(),
            'subject' => fake()->sentence(),
        ];
    }
}
