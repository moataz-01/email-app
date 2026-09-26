<?php

namespace App\Services\Mail\ConnectionTesting;

enum ConnectionFailureReason: string
{
    case HostUnreachable = 'HostUnreachable';
    case ConnectionRefused = 'ConnectionRefused';
    case TlsHandshakeFailed = 'TlsHandshakeFailed';
    case AuthenticationFailed = 'AuthenticationFailed';
    case AuthenticationUnsupported = 'AuthenticationUnsupported';
    case ProtocolError = 'ProtocolError';
    case Timeout = 'Timeout';
    case Unknown = 'Unknown';

    public function summary(): string
    {
        return match ($this) {
            self::HostUnreachable => 'The server could not be reached. Check the host name.',
            self::ConnectionRefused => 'The server refused the connection. Check the host and port.',
            self::TlsHandshakeFailed => 'A secure connection could not be established. Check the encryption setting.',
            self::AuthenticationFailed => 'The username or password was rejected.',
            self::AuthenticationUnsupported => 'This server requires an authentication method this test does not support. Use OAuth sign-in if required by your provider.',
            self::ProtocolError => 'The server responded unexpectedly.',
            self::Timeout => 'The server did not respond in time.',
            self::Unknown => 'The connection attempt failed.',
        };
    }
}
