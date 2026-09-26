<?php

namespace App\Services\Mail\ConnectionTesting;

final readonly class ConnectionTestResult
{
    private function __construct(
        public bool $successful,
        public ?ConnectionFailureReason $reason,
        public string $summary,
    ) {}

    public static function success(): self
    {
        return new self(successful: true, reason: null, summary: 'Connected successfully.');
    }

    public static function failure(ConnectionFailureReason $reason): self
    {
        return new self(successful: false, reason: $reason, summary: $reason->summary());
    }
}
