<?php

namespace App\Services\Mail\ConnectionTesting;

final readonly class SmtpConnectionSettings
{
    public function __construct(
        public string $host,
        public int $port,
        public string $encryption,
        public string $username,
        #[\SensitiveParameter] public string $password,
        public float $timeoutSeconds = 10.0,
    ) {}
}
