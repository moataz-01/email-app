<?php

namespace App\Services\Mail\Imap;

use App\Contracts\ImapSocket;
use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;
use App\Services\Mail\Imap\Exceptions\ImapAuthenticationFailedException;
use App\Services\Mail\Imap\Exceptions\ImapAuthenticationUnsupportedException;
use App\Services\Mail\Imap\Exceptions\ImapConnectionException;
use App\Services\Mail\Imap\Exceptions\ImapProtocolException;

class RawImapClient
{
    private const MAX_RESPONSE_LINES = 100;

    private int $tagCounter = 0;

    public function __construct(private readonly ImapSocket $socket) {}

    public function connectAndAuthenticate(#[\SensitiveParameter] ImapConnectionSettings $settings): void
    {
        $this->tagCounter = 0;
        try {
            if (! in_array($settings->encryption, ['tls', 'starttls'], true)) {
                throw new ImapProtocolException;
            }
            $this->socket->connect($settings->host, $settings->port, $settings->connectTimeoutSeconds);
            if ($settings->encryption === 'tls') {
                $this->socket->enableTls($settings->readTimeoutSeconds);
                $this->readGreeting($settings->readTimeoutSeconds);
                $capabilities = $this->capability($settings->readTimeoutSeconds);
            } else {
                $this->readGreeting($settings->readTimeoutSeconds);
                $capabilities = array_map(strtoupper(...), $this->capability($settings->readTimeoutSeconds));
                if (! in_array('STARTTLS', $capabilities, true)) {
                    throw new ImapProtocolException;
                }
                $this->command('STARTTLS', $settings->readTimeoutSeconds);
                $this->socket->enableTls($settings->readTimeoutSeconds);
                $capabilities = $this->capability($settings->readTimeoutSeconds);
            }

            $capabilities = array_map(strtoupper(...), $capabilities);
            if (in_array('AUTH=PLAIN', $capabilities, true)) {
                $this->authenticatePlain($settings);
            } elseif (in_array('LOGINDISABLED', $capabilities, true)) {
                throw new ImapAuthenticationUnsupportedException;
            } else {
                $this->login($settings->username, $settings->password, $settings->readTimeoutSeconds);
            }
            try {
                $this->logout($settings->readTimeoutSeconds);
            } catch (ImapConnectionException) {
                /** Authentication already succeeded; disconnect errors cannot undo that result. */
            }
        } finally {
            $this->socket->close();
        }
    }

    private function readGreeting(float $timeout): void
    {
        $line = $this->socket->readLine($timeout);
        if (! preg_match('/^\* OK(?: |\r\n)/i', $line)) {
            throw new ImapProtocolException;
        }
    }

    /** @return list<string> */
    private function capability(float $timeout): array
    {
        $untagged = $this->command('CAPABILITY', $timeout, captureUntagged: true);

        foreach ($untagged as $line) {
            if (stripos($line, '* CAPABILITY ') === 0) {
                $capabilities = preg_split('/\s+/', trim(substr($line, strlen('* CAPABILITY '))));

                return $capabilities === false ? [] : $capabilities;
            }
        }

        return [];
    }

    private function login(string $username, #[\SensitiveParameter] string $password, float $timeout): void
    {
        $tag = $this->nextTag();
        $this->socket->write(sprintf("%s LOGIN {%d}\r\n", $tag, strlen($username)));
        $this->expectContinuation($tag, $timeout);
        $this->socket->write($username);
        $this->socket->write(sprintf(" {%d}\r\n", strlen($password)));
        $this->expectContinuation($tag, $timeout);
        $this->socket->write($password);
        $this->socket->write("\r\n");
        $this->readTagged($tag, $timeout, ImapAuthenticationFailedException::class);
    }

    private function authenticatePlain(#[\SensitiveParameter] ImapConnectionSettings $settings): void
    {
        $tag = $this->nextTag();
        $this->socket->write("{$tag} AUTHENTICATE PLAIN\r\n");
        $this->expectContinuation($tag, $settings->readTimeoutSeconds);
        $this->socket->write(base64_encode("\0{$settings->username}\0{$settings->password}")."\r\n");
        $this->readTagged($tag, $settings->readTimeoutSeconds, ImapAuthenticationFailedException::class);
    }

    private function logout(float $timeout): void
    {
        $this->command('LOGOUT', $timeout);
    }

    /** @return list<string> */
    private function command(string $command, float $timeout, bool $captureUntagged = false): array
    {
        $tag = $this->nextTag();
        $this->socket->write(sprintf("%s %s\r\n", $tag, $command));

        return $this->readTagged($tag, $timeout, ImapProtocolException::class, captureUntagged: $captureUntagged, allowBye: $command === 'LOGOUT');
    }

    private function expectContinuation(string $tag, float $timeout): void
    {
        for ($lines = 0; $lines < self::MAX_RESPONSE_LINES; $lines++) {
            $line = $this->socket->readLine($timeout);
            if (preg_match('/^\+(?: |\r\n)/', $line)) {
                return;
            }
            if (preg_match('/^'.preg_quote($tag, '/').' NO(?: |\r\n)/i', $line)) {
                throw new ImapAuthenticationFailedException;
            }
            if (! str_starts_with($line, '* ') || preg_match('/^\* BYE(?: |\r\n)/i', $line)) {
                throw new ImapProtocolException;
            }
        }
        throw new ImapProtocolException;
    }

    /**
     * @param  class-string<ImapConnectionException>  $failureExceptionClass
     * @return list<string>
     */
    private function readTagged(string $tag, float $timeout, string $failureExceptionClass, bool $captureUntagged = false, bool $allowBye = false): array
    {
        $untagged = [];
        $prefix = $tag.' ';

        for ($lines = 0; $lines < self::MAX_RESPONSE_LINES; $lines++) {
            $line = $this->socket->readLine($timeout);

            if (str_starts_with($line, $prefix)) {
                $status = strtoupper(explode(' ', trim(substr($line, strlen($prefix))), 2)[0]);

                if ($status === 'OK') {
                    return $untagged;
                }

                if ($status === 'NO') {
                    throw new $failureExceptionClass;
                }

                throw new ImapProtocolException;
            }

            if (str_starts_with($line, '* ')) {
                if (! $allowBye && preg_match('/^\* BYE(?: |\r\n)/i', $line)) {
                    throw new ImapProtocolException;
                }
                if ($captureUntagged) {
                    $untagged[] = $line;
                }

                continue;
            }

            throw new ImapProtocolException;
        }
        throw new ImapProtocolException;
    }

    private function nextTag(): string
    {
        return 'a'.++$this->tagCounter;
    }
}
