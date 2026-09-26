<?php

namespace App\Services\Credentials;

use App\Contracts\CredentialCipher;
use App\Exceptions\CredentialStorageUnavailable;
use Native\Desktop\System;
use Throwable;

class NativeCredentialCipher implements CredentialCipher
{
    public function __construct(private System $system) {}

    public function available(): bool
    {
        if (! config('nativephp-internal.running') || PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        try {
            return $this->system->canEncrypt();
        } catch (Throwable) {
            return false;
        }
    }

    public function encrypt(#[\SensitiveParameter] string $plaintext): string
    {
        if (! $this->available()) {
            throw new CredentialStorageUnavailable;
        }

        try {
            $encrypted = $this->system->encrypt($plaintext);
            if (! is_string($encrypted) || $encrypted === '' || hash_equals($plaintext, $encrypted)) {
                throw new CredentialStorageUnavailable;
            }

            return $encrypted;
        } catch (Throwable) {
            throw new CredentialStorageUnavailable;
        }
    }

    public function decrypt(#[\SensitiveParameter] string $ciphertext): string
    {
        if (! $this->available()) {
            throw new CredentialStorageUnavailable;
        }

        try {
            $decrypted = $this->system->decrypt($ciphertext);
            if (! is_string($decrypted) || $decrypted === '') {
                throw new CredentialStorageUnavailable;
            }

            return $decrypted;
        } catch (Throwable) {
            throw new CredentialStorageUnavailable;
        }
    }
}
