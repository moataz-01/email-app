<?php

use App\Contracts\ImapConnectionTester;
use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;
use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use Inertia\DevTools\EntriesRepository;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeImapConnectionTester;

function imapDiagnosticInput(array $overrides = []): array
{
    return array_replace([
        'imap_host' => 'imap.example.test', 'imap_port' => 993, 'imap_encryption' => 'tls',
        'imap_username' => 'me@example.test', 'imap_password' => '  private-password  ',
    ], $overrides);
}

test('IMAP diagnostics are accessible without saved accounts or native credential storage', function () {
    config(['nativephp-internal.running' => false]);
    $this->get('/connections/imap')->assertInertia(fn (Assert $page) => $page->component('accounts/test-imap')->missing('auth'));
    $this->assertDatabaseCount('mail_accounts', 0);
});

test('an IMAP diagnostic uses transient settings and never persists them', function () {
    $fake = new FakeImapConnectionTester;
    $this->instance(ImapConnectionTester::class, $fake);

    $this->postJson('/connections/imap', imapDiagnosticInput(['verify_peer' => false]))
        ->assertOk()->assertJsonPath('successful', true)->assertJsonPath('reason', null)
        ->assertHeaderContains('Cache-Control', 'no-store')->assertDontSee('private-password')
        ->assertSessionMissing('_old_input.imap_password');

    expect($fake->calls)->toHaveCount(1);
    expect($fake->calls[0]->host)->toBe('imap.example.test');
    expect($fake->calls[0]->password)->toBe('  private-password  ');
    $this->assertDatabaseCount('mail_accounts', 0);
    $this->assertDatabaseCount('mail_credentials', 0);
    $this->assertDatabaseCount('mail_messages', 0);
});

test('an IMAP diagnostic returns a safe provider failure and allows another attempt', function () {
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester(
        ConnectionTestResult::failure(ConnectionFailureReason::AuthenticationFailed),
    ));

    $this->postJson('/connections/imap', imapDiagnosticInput())->assertOk()->assertExactJson([
        'successful' => false, 'reason' => 'AuthenticationFailed', 'summary' => 'The username or password was rejected.',
    ])->assertDontSee('private-password');

    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester);
    $this->postJson('/connections/imap', imapDiagnosticInput())->assertJsonPath('successful', true);
    $this->assertDatabaseCount('mail_credentials', 0);
});

test('invalid diagnostic settings never reach the transport', function (array $overrides, string $field) {
    $fake = new FakeImapConnectionTester;
    $this->instance(ImapConnectionTester::class, $fake);

    $this->postJson('/connections/imap', imapDiagnosticInput($overrides))->assertUnprocessable()
        ->assertJsonValidationErrors($field)->assertDontSee('private-password')
        ->assertSessionMissing('_old_input.imap_password');

    expect($fake->calls)->toBeEmpty();
})->with([
    'URL host' => [['imap_host' => 'tcp://example.test/path'], 'imap_host'],
    'bad port' => [['imap_port' => 65536], 'imap_port'],
    'plaintext' => [['imap_encryption' => 'none'], 'imap_encryption'],
    'empty username' => [['imap_username' => ''], 'imap_username'],
    'empty password' => [['imap_password' => ''], 'imap_password'],
    'control characters' => [['imap_username' => "name\r\nLOGOUT"], 'imap_username'],
]);

test('diagnostics retain CSRF protection and reject remote clients', function () {
    $fake = new FakeImapConnectionTester;
    $this->instance(ImapConnectionTester::class, $fake);
    $this->app['env'] = 'local';
    try {
        $this->postJson('/connections/imap', imapDiagnosticInput())->assertStatus(419);
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2'])
            ->postJson('/connections/imap', imapDiagnosticInput())->assertForbidden();
    } finally {
        $this->app['env'] = 'testing';
    }
    expect($fake->calls)->toBeEmpty();
});

test('diagnostic validation does not flash a password on ordinary form submissions', function () {
    $this->from('/connections/imap')->post('/connections/imap', imapDiagnosticInput(['imap_port' => 0]))
        ->assertSessionHasErrors('imap_port')->assertSessionMissing('_old_input.imap_password');
});

test('local diagnostic requests with passwords are never written to Inertia recordings', function () {
    $this->instance(ImapConnectionTester::class, new FakeImapConnectionTester);
    $this->mock(EntriesRepository::class)->shouldNotReceive('save');
    $this->app['env'] = 'local';
    try {
        $this->withSession(['_token' => 'diagnostic-csrf-token'])
            ->withHeader('X-Inertia', 'true')
            ->postJson('/connections/imap', imapDiagnosticInput(['_token' => 'diagnostic-csrf-token']))
            ->assertJsonPath('successful', true)->assertDontSee('private-password')
            ->assertSessionMissing('_old_input.imap_password');
    } finally {
        $this->app['env'] = 'testing';
    }
});

test('repeated diagnostic attempts are rate limited before contacting the provider', function () {
    $fake = new FakeImapConnectionTester;
    $this->instance(ImapConnectionTester::class, $fake);
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->postJson('/connections/imap', imapDiagnosticInput())->assertOk();
    }
    $this->postJson('/connections/imap', imapDiagnosticInput())->assertTooManyRequests()->assertDontSee('private-password');
    expect($fake->calls)->toHaveCount(6);
});
