<?php

use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;
use App\Services\Mail\Imap\Exceptions\ImapAuthenticationFailedException;
use App\Services\Mail\Imap\Exceptions\ImapProtocolException;
use App\Services\Mail\Imap\Exceptions\ImapTimeoutException;
use App\Services\Mail\Imap\Exceptions\ImapTlsHandshakeException;
use App\Services\Mail\Imap\RawImapClient;
use App\Services\Mail\Imap\StreamImapSocket;

beforeEach(function () {
    if (getenv('RUN_IMAP_SOCKET_TESTS') !== '1') {
        $this->markTestSkipped('Opt-in local TLS sockets: set RUN_IMAP_SOCKET_TESTS=1. No external hosts are contacted.');
    }
});

/** @param Closure(int, string): void $test */
function withLocalImapServer(string $mode, Closure $test): void
{
    $keyPath = tempnam(sys_get_temp_dir(), 'imap-test-key-');
    $certPath = tempnam(sys_get_temp_dir(), 'imap-test-cert-');
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
    $cert = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
    openssl_pkey_export_to_file($key, $keyPath);
    openssl_x509_export_to_file($cert, $certPath);
    $process = proc_open([PHP_BINARY, __DIR__.'/../Fakes/imap-tls-server.php', $certPath, $keyPath, $mode],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    try {
        stream_set_timeout($pipes[1], 5);
        $address = trim(fgets($pipes[1]) ?: '');
        expect($address)->toStartWith('127.0.0.1:');
        $test((int) substr(strrchr($address, ':'), 1), $certPath);
    } finally {
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_terminate($process);
        proc_close($process);
        unlink($keyPath);
        unlink($certPath);
    }
}

test('real socket transport negotiates verified TLS and authenticates', function (string $mode) {
    withLocalImapServer($mode, function (int $port, string $certPath) use ($mode): void {
        $client = new RawImapClient(new StreamImapSocket(caFile: $certPath));
        expect(fn () => $client->connectAndAuthenticate(new ImapConnectionSettings('localhost', $port, $mode, 'test-user', 'test-password')))
            ->not->toThrow(Throwable::class);
    });
})->with(['tls', 'starttls'])->group('imap-sockets');

test('real socket transport rejects untrusted certificates and hostname mismatch', function (bool $trust, string $host) {
    withLocalImapServer('tls', function (int $port, string $certPath) use ($trust, $host): void {
        $client = new RawImapClient(new StreamImapSocket(caFile: $trust ? $certPath : null));
        expect(fn () => $client->connectAndAuthenticate(new ImapConnectionSettings($host, $port, 'tls', 'test-user', 'test-password')))
            ->toThrow(ImapTlsHandshakeException::class);
    });
})->with(['untrusted' => [false, 'localhost'], 'wrong hostname' => [true, '127.0.0.1']])->group('imap-sockets');

test('real socket transport rejects malformed response lines', function (string $mode) {
    withLocalImapServer($mode, function (int $port, string $certPath): void {
        $client = new RawImapClient(new StreamImapSocket(caFile: $certPath));
        expect(fn () => $client->connectAndAuthenticate(new ImapConnectionSettings('localhost', $port, 'tls', 'test-user', 'test-password')))
            ->toThrow(ImapProtocolException::class);
    });
})->with(['oversized', 'truncated'])->group('imap-sockets');

test('real socket login rejection maps to authentication failure', function () {
    withLocalImapServer('tls', function (int $port, string $certPath): void {
        $client = new RawImapClient(new StreamImapSocket(caFile: $certPath));
        expect(fn () => $client->connectAndAuthenticate(new ImapConnectionSettings('localhost', $port, 'tls', 'test-user', 'wrong-password')))
            ->toThrow(ImapAuthenticationFailedException::class);
    });
})->group('imap-sockets');

test('the total attempt budget bounds a server that keeps sending replies', function () {
    withLocalImapServer('slow', function (int $port, string $certPath): void {
        $client = new RawImapClient(new StreamImapSocket(maxDurationSeconds: 0.2, caFile: $certPath));
        expect(fn () => $client->connectAndAuthenticate(new ImapConnectionSettings('localhost', $port, 'tls', 'test-user', 'test-password')))
            ->toThrow(ImapTimeoutException::class);
    });
})->group('imap-sockets');
