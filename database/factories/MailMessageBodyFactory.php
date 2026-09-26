<?php

namespace Database\Factories;

use App\Models\MailMessage;
use App\Models\MailMessageBody;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailMessageBody> */
class MailMessageBodyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mail_message_id' => MailMessage::factory(),
            'text' => fake()->paragraph(),
        ];
    }
}
