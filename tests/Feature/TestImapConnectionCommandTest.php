<?php

use App\Contracts\ImapConnectionTester;
use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;
use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use Tests\Fakes\FakeImapConnectionTester;

test('the IMAP command tests settings with a hidden password and creates no account', function () {
    $fake = new FakeImapConnectionTester;
    $this->instance(ImapConnectionTester::class, $fake);

    $this->artisan('mail:test-imap', ['--host' => 'imap.example.test', '--username' => 'me@example.test'])
        ->expectsQuestion('IMAP password or app password', 'cli-test-secret')
        ->expectsOutputToContain('IMAP login succeeded')->assertSuccessful();

    expect($fake->calls)->toHaveCount(1);
    expect($fake->calls[0]->port)->toBe(993);
    expect($fake->calls[0]->password)->toBe('cli-test-secret');
    $this->assertDatabaseCount('mail_accounts', 0);
    $this->assertDatabaseCount('mail_credentials', 0);
});

test('the command reports provider failure with a failing exit status', function () {
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(
        ConnectionTestResult::failure(ConnectionFailureReason::AuthenticationFailed),
    ));

    $this->artisan('mail:test-imap', ['--host' => 'imap.example.test', '--username' => 'me', '--encryption' => 'starttls'])
        ->expectsQuestion('IMAP password or app password', 'wrong-secret')
        ->expectsOutputToContain('The username or password was rejected.')->assertFailed();
});

test('invalid CLI settings are rejected before asking for credentials', function () {
    $fake = new FakeImapConnectionTester;
    $this->instance(ImapConnectionTester::class, $fake);

    $this->artisan('mail:test-imap', ['--host' => 'tcp://bad/path', '--username' => 'me'])->assertFailed();
    expect($fake->calls)->toBeEmpty();
});

test('noninteractive use cannot pass passwords in command arguments', function () {
    $fake = new FakeImapConnectionTester;
    $this->instance(ImapConnectionTester::class, $fake);

    $this->artisan('mail:test-imap', ['--no-interaction' => true, '--host' => 'imap.example.test', '--username' => 'me'])
        ->expectsOutputToContain('Run this command interactively')->assertFailed();
    expect($fake->calls)->toBeEmpty();
});
