<?php

namespace App\Contracts;

interface ImapSocket
{
    public function connect(string $host, int $port, float $timeoutSeconds): void;

    public function enableTls(float $timeoutSeconds): void;

    public function write(string $data): void;

    public function readLine(float $timeoutSeconds): string;

    public function close(): void;
}
