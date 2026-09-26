<?php

namespace Database\Factories;

use App\Models\MailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailAccount> */
class MailAccountFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'provider' => 'imap_smtp',
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
