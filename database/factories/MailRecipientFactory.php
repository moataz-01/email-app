<?php

namespace Database\Factories;

use App\Models\MailMessage;
use App\Models\MailRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailRecipient> */
class MailRecipientFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mail_message_id' => MailMessage::factory(),
            'kind' => 'to',
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
        ];
    }
}
