<?php

namespace App\Services\Mail\ConnectionTesting;

final readonly class ImapConnectionSettings
{
    public function __construct(
        public string $host,
        public int $port,
        public string $encryption,
        public string $username,
        #[\SensitiveParameter] public string $password,
        public float $connectTimeoutSeconds = 5.0,
        public float $readTimeoutSeconds = 5.0,
    ) {}
}
