<?php

namespace App\Services\Mail\Imap;

use App\Contracts\ImapSocket;
use App\Services\Mail\Imap\Exceptions\ImapConnectionRefusedException;
use App\Services\Mail\Imap\Exceptions\ImapHostUnreachableException;
use App\Services\Mail\Imap\Exceptions\ImapProtocolException;
use App\Services\Mail\Imap\Exceptions\ImapTimeoutException;
use App\Services\Mail\Imap\Exceptions\ImapTlsHandshakeException;

class StreamImapSocket implements ImapSocket
{
    /** @var resource|null */
    private $stream;

    private float $deadline = 0;

    public function __construct(private readonly float $maxDurationSeconds = 20.0, private readonly ?string $caFile = null) {}

    public function connect(string $host, int $port, float $timeoutSeconds): void
    {
        $this->close();
        $this->deadline = hrtime(true) / 1_000_000_000 + $this->maxDurationSeconds;
        $ssl = ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'allow_self_signed' => false];
        if ($this->caFile !== null) {
            $ssl['cafile'] = $this->caFile;
        }
        $context = stream_context_create([
            'ssl' => $ssl,
        ]);

        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $this->remainingTimeout($timeoutSeconds),
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($stream === false) {
            throw match ($errno) {
                60, 110, 10060 => new ImapTimeoutException,
                61, 111, 10061 => new ImapConnectionRefusedException,
                default => new ImapHostUnreachableException,
            };
        }

        $this->stream = $stream;
        $this->applyTimeout($stream, $timeoutSeconds);
    }

    public function enableTls(float $timeoutSeconds): void
    {
        if ($this->stream === null) {
            throw new ImapTlsHandshakeException;
        }

        $this->applyTimeout($this->stream, $timeoutSeconds);

        $enabled = @stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);

        if (stream_get_meta_data($this->stream)['timed_out']) {
            throw new ImapTimeoutException;
        }
        if ($enabled !== true) {
            throw new ImapTlsHandshakeException;
        }
    }

    public function write(#[\SensitiveParameter] string $data): void
    {
        if ($this->stream === null) {
            throw new ImapProtocolException;
        }
        $offset = 0;
        while ($offset < strlen($data)) {
            $this->applyTimeout($this->stream, 5.0);
            $written = @fwrite($this->stream, substr($data, $offset));
            if (stream_get_meta_data($this->stream)['timed_out']) {
                throw new ImapTimeoutException;
            }
            if ($written === false || $written === 0) {
                throw new ImapProtocolException;
            }
            $offset += $written;
        }
    }

    public function readLine(float $timeoutSeconds): string
    {
        if ($this->stream === null) {
            throw new ImapProtocolException;
        }

        $this->applyTimeout($this->stream, $timeoutSeconds);

        $line = @fgets($this->stream, 8192);
        $meta = stream_get_meta_data($this->stream);

        if ($meta['timed_out']) {
            throw new ImapTimeoutException;
        }

        if ($line === false || ! str_ends_with($line, "\r\n")) {
            throw new ImapProtocolException;
        }

        return $line;
    }

    public function close(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    /** @param resource $stream */
    private function applyTimeout($stream, float $timeoutSeconds): void
    {
        $timeoutSeconds = $this->remainingTimeout($timeoutSeconds);
        stream_set_timeout($stream, (int) $timeoutSeconds, (int) (($timeoutSeconds - (int) $timeoutSeconds) * 1_000_000));
    }

    private function remainingTimeout(float $timeoutSeconds): float
    {
        $remaining = $this->deadline - hrtime(true) / 1_000_000_000;
        if ($remaining <= 0 || $timeoutSeconds <= 0) {
            throw new ImapTimeoutException;
        }

        return min($remaining, $timeoutSeconds);
    }
}
