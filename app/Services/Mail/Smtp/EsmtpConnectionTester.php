<?php

namespace App\Services\Mail\Smtp;

use App\Contracts\SmtpConnectionTester;
use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;
use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use App\Services\Mail\ConnectionTesting\SmtpConnectionSettings;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Throwable;

class EsmtpConnectionTester implements SmtpConnectionTester
{
    /** @var list<int> */
    private const AUTH_FAILURE_CODES = [432, 454, 504, 530, 534, 535, 538];

    public function test(SmtpConnectionSettings $settings): ConnectionTestResult
    {
        $transport = new EsmtpTransport(
            host: $settings->host,
            port: $settings->port,
            tls: $settings->encryption === 'tls',
        );
        $transport->setUsername($settings->username);
        $transport->setPassword($settings->password);
        // Required even for implicit TLS (harmless there): for 'starttls' this is what stops
        // a silent fallback to plaintext when the server doesn't advertise STARTTLS support.
        $transport->setRequireTls(true);

        $stream = $transport->getStream();
        if ($stream instanceof SocketStream) {
            $stream->setTimeout($settings->timeoutSeconds);
        }

        try {
            $transport->start();
            $transport->stop();

            return ConnectionTestResult::success();
        } catch (TransportExceptionInterface $exception) {
            return ConnectionTestResult::failure($this->classify($exception));
        } catch (Throwable) {
            return ConnectionTestResult::failure(ConnectionFailureReason::Unknown);
        }
    }

    private function classify(TransportExceptionInterface $exception): ConnectionFailureReason
    {
        $code = $exception->getCode();

        if ($code !== 0) {
            return in_array($code, self::AUTH_FAILURE_CODES, true)
                ? ConnectionFailureReason::AuthenticationFailed
                : ConnectionFailureReason::ProtocolError;
        }

        // No SMTP reply code means the failure happened before any structured server
        // response existed (DNS, connect, TLS negotiation) — Symfony's own message text
        // here is PHP/stream-error driven and never embeds a raw server line or credentials,
        // unlike the server-response text carried by exceptions that do have a numeric code.
        $message = $exception->getMessage();

        return match (true) {
            str_contains($message, 'STARTTLS'), str_contains($message, 'TLS required') => ConnectionFailureReason::TlsHandshakeFailed,
            str_contains($message, 'timed out'), str_contains($message, 'timeout') => ConnectionFailureReason::Timeout,
            str_contains($message, 'Connection refused') => ConnectionFailureReason::ConnectionRefused,
            str_contains($message, 'getaddrinfo'), str_contains($message, 'Name or service not known') => ConnectionFailureReason::HostUnreachable,
            default => ConnectionFailureReason::Unknown,
        };
    }
}
