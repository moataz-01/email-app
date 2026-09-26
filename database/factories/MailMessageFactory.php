<?php

namespace Database\Factories;

use App\Models\MailAccount;
use App\Models\MailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailMessage> */
class MailMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mail_account_id' => MailAccount::factory(),
            'provider_id' => fake()->uuid(),
            'subject' => fake()->sentence(),
            'from_name' => fake()->name(),
            'from_email' => fake()->safeEmail(),
            'snippet' => fake()->sentence(),
            'received_at' => now(),
        ];
    }
}
