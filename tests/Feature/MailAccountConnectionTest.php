<?php

use App\Contracts\CredentialCipher;
use App\Contracts\ImapConnectionTester;
use App\Contracts\SmtpConnectionTester;
use App\Exceptions\CredentialStorageUnavailable;
use App\Models\MailAccount;
use App\Services\Credentials\CredentialStore;
use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;
use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use Illuminate\Support\Facades\DB;
use Inertia\DevTools\DevTools;
use Inertia\DevTools\EntriesRepository;
use Tests\Fakes\FakeCredentialCipher;
use Tests\Fakes\FakeImapConnectionTester;
use Tests\Fakes\FakeSmtpConnectionTester;

function createConfiguredMailAccount(array $overrides = []): MailAccount
{
    $account = MailAccount::factory()->create(array_replace([
        'imap_host' => 'imap.example.test', 'imap_port' => 993, 'imap_encryption' => 'tls', 'imap_username' => 'me@example.test',
        'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_encryption' => 'starttls', 'smtp_username' => 'me@example.test',
    ], $overrides));

    app(CredentialStore::class)->put($account, ['imap_password' => 'imap-secret', 'smtp_password' => 'smtp-secret']);

    return $account;
}

beforeEach(function () {
    $this->withoutVite();
    $this->instance(CredentialCipher::class, new FakeCredentialCipher);
});

test('a successful test on both protocols marks the account connected', function () {
    $account = createConfiguredMailAccount();
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(ConnectionTestResult::success()));
    $this->instance(SmtpConnectionTester::class, new FakeSmtpConnectionTester(ConnectionTestResult::success()));

    $this->post("/accounts/{$account->id}/test-connection")->assertRedirect('/accounts')
        ->assertSessionHasNoErrors()->assertInertiaFlash('toast.type', 'success');

    $account->refresh();
    expect($account->status)->toBe('connected');
    expect($account->imap_status)->toBe('connected');
    expect($account->smtp_status)->toBe('connected');
    expect($account->last_connection_tested_at)->not->toBeNull();
});

test('an IMAP failure with a successful SMTP test marks the account connection_failed and identifies the protocol', function () {
    $account = createConfiguredMailAccount();
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(ConnectionTestResult::failure(ConnectionFailureReason::AuthenticationFailed)));
    $this->instance(SmtpConnectionTester::class, new FakeSmtpConnectionTester(ConnectionTestResult::success()));

    $this->post("/accounts/{$account->id}/test-connection")->assertRedirect('/accounts')->assertInertiaFlash('toast.type', 'error');

    $account->refresh();
    expect($account->status)->toBe('connection_failed');
    expect($account->imap_status)->toBe('failed');
    expect($account->smtp_status)->toBe('connected');
});

test('an SMTP failure with a successful IMAP test marks the account connection_failed and identifies the protocol', function () {
    $account = createConfiguredMailAccount();
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(ConnectionTestResult::success()));
    $this->instance(SmtpConnectionTester::class, new FakeSmtpConnectionTester(ConnectionTestResult::failure(ConnectionFailureReason::AuthenticationFailed)));

    $this->post("/accounts/{$account->id}/test-connection")->assertRedirect('/accounts');

    $account->refresh();
    expect($account->status)->toBe('connection_failed');
    expect($account->imap_status)->toBe('connected');
    expect($account->smtp_status)->toBe('failed');
});

test('connection failure reasons map to a generic message without provider text', function (ConnectionFailureReason $reason) {
    $account = createConfiguredMailAccount();
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(ConnectionTestResult::failure($reason)));
    $this->instance(SmtpConnectionTester::class, new FakeSmtpConnectionTester(ConnectionTestResult::success()));

    $this->post("/accounts/{$account->id}/test-connection")->assertInertiaFlash('toast.type', 'error');

    expect($account->refresh()->imap_status)->toBe('failed');
})->with([
    'host unreachable' => [ConnectionFailureReason::HostUnreachable],
    'connection refused' => [ConnectionFailureReason::ConnectionRefused],
    'TLS handshake failed' => [ConnectionFailureReason::TlsHandshakeFailed],
    'timeout' => [ConnectionFailureReason::Timeout],
]);

test('unavailable credential storage leaves the account untouched and never invokes a provider', function () {
    $account = createConfiguredMailAccount(['status' => 'not_connected', 'imap_status' => null, 'smtp_status' => null]);
    $this->mock(CredentialCipher::class)->shouldReceive('decrypt')->andThrow(new CredentialStorageUnavailable);
    $imap = $this->mock(ImapConnectionTester::class);
    $imap->shouldNotReceive('test');
    $smtp = $this->mock(SmtpConnectionTester::class);
    $smtp->shouldNotReceive('test');

    $this->post("/accounts/{$account->id}/test-connection")->assertRedirect('/accounts')->assertInertiaFlash('toast.type', 'error');

    $account->refresh();
    expect($account->status)->toBe('not_connected');
    expect($account->imap_status)->toBeNull();
    expect($account->smtp_status)->toBeNull();
    expect($account->last_connection_tested_at)->toBeNull();
});

test('testing a nonexistent account is not found', function () {
    $this->post('/accounts/999/test-connection')->assertNotFound();
});

test('the request body cannot override the host or port that gets tested', function () {
    $account = createConfiguredMailAccount();
    $imap = new FakeImapConnectionTester(ConnectionTestResult::success());
    $this->instance(ImapConnectionTester::class, $imap);
    $this->instance(SmtpConnectionTester::class, new FakeSmtpConnectionTester(ConnectionTestResult::success()));

    $this->post("/accounts/{$account->id}/test-connection", ['imap_host' => 'attacker.example.test', 'imap_port' => 25]);

    expect($imap->calls)->toHaveCount(1);
    expect($imap->calls[0]->host)->toBe('imap.example.test');
    expect($imap->calls[0]->port)->toBe(993);
});

test('repeated tests keep only the latest result and advance the tested-at timestamp', function () {
    $account = createConfiguredMailAccount();
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(ConnectionTestResult::failure(ConnectionFailureReason::AuthenticationFailed)));
    $this->instance(SmtpConnectionTester::class, new FakeSmtpConnectionTester(ConnectionTestResult::success()));
    $this->post("/accounts/{$account->id}/test-connection");
    $firstTestedAt = $account->refresh()->last_connection_tested_at;

    $this->travel(1)->minutes();
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(ConnectionTestResult::success()));
    $this->post("/accounts/{$account->id}/test-connection");

    $account->refresh();
    expect($account->status)->toBe('connected');
    expect($account->imap_status)->toBe('connected');
    expect($account->last_connection_tested_at->gt($firstTestedAt))->toBeTrue();
});

test('secrets never appear in the response, session, or development request recording', function () {
    $account = createConfiguredMailAccount();
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(ConnectionTestResult::success()));
    $this->instance(SmtpConnectionTester::class, new FakeSmtpConnectionTester(ConnectionTestResult::success()));
    $recorderStorage = $this->mock(EntriesRepository::class);
    $recorderStorage->shouldReceive('save')->andReturnNull();
    $recorderStorage->shouldReceive('pruneIfDue')->andReturnNull();
    $this->app['env'] = 'local';

    try {
        $response = $this->withSession(['_token' => 'test-csrf-token'])
            ->withHeaders(['X-Inertia' => 'true'])
            ->post("/accounts/{$account->id}/test-connection", ['_token' => 'test-csrf-token']);
        $response->assertRedirect('/accounts');
        $recorderStorage->shouldNotHaveReceived('save');
        expect(DevTools::recorder())->toBeNull();
    } finally {
        $this->app['env'] = 'testing';
    }

    expect(DB::table('mail_credentials')->value('ciphertext'))->not->toContain('imap-secret', 'smtp-secret');
});

test('saving an account now points the user at testing the connection instead of claiming it is unavailable', function () {
    $this->post('/accounts', [
        'name' => 'Work', 'email' => 'me@example.test',
        'imap_host' => 'imap.example.test', 'imap_port' => 993, 'imap_encryption' => 'tls',
        'imap_username' => 'me@example.test', 'imap_password' => 'imap-password-value',
        'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_encryption' => 'starttls',
        'smtp_username' => 'me@example.test', 'smtp_password' => 'smtp-password-value',
    ])->assertInertiaFlash('toast.message', 'Account settings saved. Test the connection to make sure it works.');
});
