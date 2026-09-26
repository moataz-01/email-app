<?php

use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;
use App\Services\Mail\Imap\Exceptions\ImapAuthenticationFailedException;
use App\Services\Mail\Imap\Exceptions\ImapAuthenticationUnsupportedException;
use App\Services\Mail\Imap\Exceptions\ImapProtocolException;
use App\Services\Mail\Imap\Exceptions\ImapTimeoutException;
use App\Services\Mail\Imap\RawImapClient;
use Tests\Fakes\ScriptedImapSocket;

function imapSettings(string $encryption): ImapConnectionSettings
{
    return new ImapConnectionSettings(
        host: 'imap.example.test',
        port: $encryption === 'tls' ? 993 : 143,
        encryption: $encryption,
        username: 'alice',
        password: 'secret12',
    );
}

test('a successful implicit TLS login enables TLS immediately and sends CAPABILITY, a literal LOGIN and LOGOUT', function () {
    $socket = new ScriptedImapSocket([
        "* OK IMAP4rev1 Service Ready\r\n",
        "* CAPABILITY IMAP4rev1\r\n",
        "a1 OK CAPABILITY completed\r\n",
        "+ OK\r\n",
        "+ OK\r\n",
        "a2 OK LOGIN completed\r\n",
        "* BYE Logging out\r\n",
        "a3 OK LOGOUT completed\r\n",
    ]);

    (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls'));

    expect($socket->tlsEnabled)->toBeTrue();
    expect($socket->closed)->toBeTrue();
    expect($socket->written)->toBe([
        "a1 CAPABILITY\r\n",
        "a2 LOGIN {5}\r\n",
        'alice',
        " {8}\r\n",
        'secret12',
        "\r\n",
        "a3 LOGOUT\r\n",
    ]);
});

test('a successful STARTTLS login upgrades before authenticating and re-issues CAPABILITY afterward', function () {
    $socket = new ScriptedImapSocket([
        "* OK IMAP4rev1 Service Ready\r\n",
        "* CAPABILITY IMAP4rev1 STARTTLS\r\n",
        "a1 OK CAPABILITY completed\r\n",
        "a2 OK Begin TLS negotiation now\r\n",
        "* CAPABILITY IMAP4rev1\r\n",
        "a3 OK CAPABILITY completed\r\n",
        "+ OK\r\n",
        "+ OK\r\n",
        "a4 OK LOGIN completed\r\n",
        "* BYE Logging out\r\n",
        "a5 OK LOGOUT completed\r\n",
    ]);

    (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('starttls'));

    expect($socket->tlsEnabled)->toBeTrue();
    expect($socket->written)->toBe([
        "a1 CAPABILITY\r\n",
        "a2 STARTTLS\r\n",
        "a3 CAPABILITY\r\n",
        "a4 LOGIN {5}\r\n",
        'alice',
        " {8}\r\n",
        'secret12',
        "\r\n",
        "a5 LOGOUT\r\n",
    ]);
});

test('a tagged NO on LOGIN throws an authentication failure', function () {
    $socket = new ScriptedImapSocket([
        "* OK IMAP4rev1 Service Ready\r\n",
        "* CAPABILITY IMAP4rev1\r\n",
        "a1 OK CAPABILITY completed\r\n",
        "+ OK\r\n",
        "+ OK\r\n",
        "a2 NO Authentication failed\r\n",
    ]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapAuthenticationFailedException::class);
});

test('a tagged BAD response is a protocol error rather than an authentication failure', function () {
    $socket = new ScriptedImapSocket([
        "* OK IMAP4rev1 Service Ready\r\n",
        "a1 BAD Unknown command\r\n",
    ]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapProtocolException::class);
});

test('STARTTLS is refused when the server does not advertise it', function () {
    $socket = new ScriptedImapSocket([
        "* OK IMAP4rev1 Service Ready\r\n",
        "* CAPABILITY IMAP4rev1\r\n",
        "a1 OK CAPABILITY completed\r\n",
    ]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('starttls')))
        ->toThrow(ImapProtocolException::class);
});

test('an exhausted response queue is treated as a timeout', function () {
    $socket = new ScriptedImapSocket([]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapTimeoutException::class);
});

test('SASL PLAIN is preferred when advertised over TLS', function () {
    $socket = new ScriptedImapSocket([
        "* OK Ready\r\n", "* CAPABILITY IMAP4rev1 AUTH=PLAIN LOGINDISABLED\r\n", "a1 OK done\r\n",
        "+ \r\n", "a2 OK authenticated\r\n", "* BYE logout\r\n", "a3 OK done\r\n",
    ]);

    (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls'));

    expect($socket->written)->toBe([
        "a1 CAPABILITY\r\n", "a2 AUTHENTICATE PLAIN\r\n", base64_encode("\0alice\0secret12")."\r\n", "a3 LOGOUT\r\n",
    ]);
    expect($socket->closed)->toBeTrue();
});

test('a rejected literal before continuation is an authentication failure', function () {
    $socket = new ScriptedImapSocket([
        "* OK Ready\r\n", "* CAPABILITY IMAP4rev1\r\n", "a1 OK done\r\n", "a2 NO rejected\r\n",
    ]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapAuthenticationFailedException::class);
    expect(implode('', $socket->written))->not->toContain('secret12');
    expect($socket->closed)->toBeTrue();
});

test('unsolicited status replies before continuations do not break login', function () {
    $socket = new ScriptedImapSocket([
        "* ok Ready\r\n", "* CAPABILITY IMAP4rev1\r\n", "a1 OK done\r\n",
        "* OK notice\r\n", "+ continue\r\n", "+ continue\r\n", "a2 OK done\r\n", "a3 OK done\r\n",
    ]);

    (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls'));
    expect($socket->closed)->toBeTrue();
});

test('a server cannot keep the client busy with unlimited untagged responses', function () {
    $socket = new ScriptedImapSocket([
        "* OK Ready\r\n", ...array_fill(0, 101, "* OK still waiting\r\n"), "a1 OK done\r\n",
    ]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapProtocolException::class);
    expect($socket->closed)->toBeTrue();
});

test('invalid tagged success text is never accepted', function () {
    $socket = new ScriptedImapSocket(["* OK Ready\r\n", "a1 OKAY invalid\r\n"]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapProtocolException::class);
});

test('logout failure does not erase an already successful authentication', function () {
    $socket = new ScriptedImapSocket([
        "* OK Ready\r\n", "* CAPABILITY IMAP4rev1\r\n", "a1 OK done\r\n",
        "+ go\r\n", "+ go\r\n", "a2 OK logged in\r\n",
    ]);

    (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls'));
    expect($socket->closed)->toBeTrue();
});

test('OAuth-only capabilities never cause a password LOGIN attempt', function () {
    $socket = new ScriptedImapSocket([
        "* OK Ready\r\n", "* CAPABILITY IMAP4rev1 AUTH=XOAUTH2 LOGINDISABLED\r\n", "a1 OK done\r\n",
    ]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapAuthenticationUnsupportedException::class);
    expect($socket->written)->toBe(["a1 CAPABILITY\r\n"]);
});

test('SASL rejection never triggers another password attempt', function () {
    $socket = new ScriptedImapSocket([
        "* OK Ready\r\n", "* CAPABILITY IMAP4rev1 AUTH=PLAIN\r\n", "a1 OK done\r\n",
        "+ continue\r\n", "a2 NO invalid credentials\r\n",
    ]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapAuthenticationFailedException::class);
    expect($socket->written)->toBe([
        "a1 CAPABILITY\r\n", "a2 AUTHENTICATE PLAIN\r\n", base64_encode("\0alice\0secret12")."\r\n",
    ]);
});

test('server shutdown before authentication stops processing immediately', function () {
    $socket = new ScriptedImapSocket(["* OK Ready\r\n", "* BYE unavailable\r\n"]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('tls')))
        ->toThrow(ImapProtocolException::class);
    expect($socket->closed)->toBeTrue();
});

test('unsupported encryption settings never send credentials', function () {
    $socket = new ScriptedImapSocket([]);

    expect(fn () => (new RawImapClient($socket))->connectAndAuthenticate(imapSettings('none')))
        ->toThrow(ImapProtocolException::class);
    expect($socket->written)->toBeEmpty();
});
