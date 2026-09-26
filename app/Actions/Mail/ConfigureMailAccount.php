<?php

namespace App\Actions\Mail;

use App\Models\MailAccount;
use App\Services\Credentials\CredentialStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ConfigureMailAccount
{
    public function __construct(private CredentialStore $credentials) {}

    /** @param array<string, mixed> $settings */
    public function create(#[\SensitiveParameter] array $settings): MailAccount
    {
        return DB::transaction(function () use ($settings): MailAccount {
            $account = MailAccount::query()->create([
                ...Arr::except($settings, ['imap_password', 'smtp_password']),
                'provider' => 'imap_smtp', 'status' => 'not_connected',
            ]);
            $this->credentials->put($account, [
                'imap_password' => $settings['imap_password'], 'smtp_password' => $settings['smtp_password'],
            ]);

            return $account;
        });
    }
}
