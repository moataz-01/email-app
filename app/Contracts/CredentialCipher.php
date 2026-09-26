<?php

namespace App\Contracts;

interface CredentialCipher
{
    public function available(): bool;

    public function encrypt(#[\SensitiveParameter] string $plaintext): string;

    public function decrypt(#[\SensitiveParameter] string $ciphertext): string;
}
