<?php

namespace Tests\Fakes;

use App\Contracts\ImapSocket;
use App\Services\Mail\Imap\Exceptions\ImapTimeoutException;

class ScriptedImapSocket implements ImapSocket
{
    /** @var list<string> */
    public array $written = [];

    public bool $tlsEnabled = false;

    public bool $closed = false;

    private int $readIndex = 0;

    /** @param list<string> $responses lines returned in order from readLine() */
    public function __construct(private array $responses) {}

    public function connect(string $host, int $port, float $timeoutSeconds): void {}

    public function enableTls(float $timeoutSeconds): void
    {
        $this->tlsEnabled = true;
    }

    public function write(string $data): void
    {
        $this->written[] = $data;
    }

    public function readLine(float $timeoutSeconds): string
    {
        if (! array_key_exists($this->readIndex, $this->responses)) {
            throw new ImapTimeoutException;
        }

        return $this->responses[$this->readIndex++];
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
