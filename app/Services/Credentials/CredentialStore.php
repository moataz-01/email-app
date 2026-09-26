<?php

namespace App\Services\Credentials;

use App\Contracts\CredentialCipher;
use App\Exceptions\CredentialStorageUnavailable;
use App\Models\MailAccount;
use App\Models\MailCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CredentialStore
{
    public function __construct(private CredentialCipher $cipher) {}

    /** @param array<string, string> $secrets */
    public function put(MailAccount $account, #[\SensitiveParameter] array $secrets): void
    {
        $encrypted = $this->cipher->encrypt(json_encode([
            'account_id' => $account->id, 'secrets' => $secrets,
        ], JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($account, $encrypted): void {
            $credential = MailCredential::query()->where('mail_account_id', $account->id)->first();
            if ($credential === null) {
                $credential = new MailCredential(['id' => (string) Str::uuid(), 'mail_account_id' => $account->id]);
            }
            $credential->ciphertext = $encrypted;
            $credential->save();
            $account->update(['credential_reference' => $credential->id]);
        });
    }

    /** @return array<string, string> */
    public function get(MailAccount $account): array
    {
        try {
            $credential = MailCredential::query()->where('mail_account_id', $account->id)
                ->whereKey($account->credential_reference)->firstOrFail();
            $payload = json_decode($this->cipher->decrypt($credential->ciphertext), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($payload) || ($payload['account_id'] ?? null) !== $account->id || ! is_array($payload['secrets'] ?? null)) {
                throw new CredentialStorageUnavailable;
            }
            foreach ($payload['secrets'] as $key => $value) {
                if (! is_string($key) || ! is_string($value)) {
                    throw new CredentialStorageUnavailable;
                }
            }

            return $payload['secrets'];
        } catch (Throwable) {
            throw new CredentialStorageUnavailable;
        }
    }
}
