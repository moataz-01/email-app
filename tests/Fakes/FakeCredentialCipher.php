<?php

namespace Tests\Fakes;

use App\Contracts\CredentialCipher;
use Illuminate\Encryption\Encrypter;

class FakeCredentialCipher implements CredentialCipher
{
    private Encrypter $encrypter;

    public function __construct()
    {
        $this->encrypter = new Encrypter(Encrypter::generateKey('aes-256-gcm'), 'aes-256-gcm');
    }

    public function available(): bool
    {
        return true;
    }

    public function encrypt(#[\SensitiveParameter] string $plaintext): string
    {
        return $this->encrypter->encryptString($plaintext);
    }

    public function decrypt(#[\SensitiveParameter] string $ciphertext): string
    {
        return $this->encrypter->decryptString($ciphertext);
    }
}
