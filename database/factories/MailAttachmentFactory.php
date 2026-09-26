<?php

namespace Database\Factories;

use App\Models\MailAttachment;
use App\Models\MailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailAttachment> */
class MailAttachmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'mail_message_id' => MailMessage::factory(),
            'provider_id' => fake()->uuid(),
            'filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
