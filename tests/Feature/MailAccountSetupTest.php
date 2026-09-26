<?php

use App\Contracts\CredentialCipher;
use App\Exceptions\CredentialStorageUnavailable;
use App\Models\MailAccount;
use App\Services\Credentials\CredentialStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\DevTools\DevTools;
use Inertia\DevTools\EntriesRepository;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeCredentialCipher;

function mailAccountInput(array $overrides = []): array
{
    return array_replace([
        'name' => 'Work', 'email' => 'me@example.test',
        'imap_host' => 'imap.example.test', 'imap_port' => 993, 'imap_encryption' => 'tls',
        'imap_username' => 'me@example.test', 'imap_password' => 'imap-password-value',
        'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_encryption' => 'starttls',
        'smtp_username' => 'me@example.test', 'smtp_password' => 'smtp-password-value',
    ], $overrides);
}

beforeEach(function () {
    $this->withoutVite();
});

test('valid account configuration stores secrets atomically without connecting to the provider', function () {
    Http::preventStrayRequests();
    $this->instance(CredentialCipher::class, new FakeCredentialCipher);
    $this->post('/accounts', mailAccountInput())->assertRedirect('/accounts')->assertSessionHasNoErrors();

    $account = MailAccount::query()->sole();
    expect($account->status)->toBe('not_connected');
    expect($account->imap_port)->toBe(993);
    expect($account->smtp_encryption)->toBe('starttls');
    expect(app(CredentialStore::class)->get($account))->toBe([
        'imap_password' => 'imap-password-value', 'smtp_password' => 'smtp-password-value',
    ]);
    expect(DB::table('mail_credentials')->value('ciphertext'))->not->toContain('imap-password-value', 'smtp-password-value');
    $this->assertDatabaseCount('users', 0);
    $this->get('/accounts')->assertOk()->assertInertia(fn (Assert $page) => $page->component('accounts/index')
        ->has('accounts', 1)->missing('accounts.0.credential_reference')->missing('accounts.0.imap_password')
        ->missing('accounts.0.legacy_user_id')->where('accounts.0.status', 'not_connected'));
});

test('failed credential encryption rolls back the account and does not flash passwords', function () {
    $this->mock(CredentialCipher::class)->shouldReceive('encrypt')->once()->andThrow(new CredentialStorageUnavailable);
    $this->from('/accounts/create')->post('/accounts', mailAccountInput())
        ->assertRedirect('/accounts/create')->assertSessionHasErrors('credentials')
        ->assertSessionMissing('_old_input.imap_password')->assertSessionMissing('_old_input.smtp_password');
    $this->assertDatabaseCount('mail_accounts', 0);
    $this->assertDatabaseCount('mail_credentials', 0);
});

test('invalid account settings are rejected without storing or flashing secrets', function (array $overrides, string $field) {
    $this->post('/accounts', mailAccountInput($overrides))->assertSessionHasErrors($field)
        ->assertSessionMissing('_old_input.imap_password')->assertSessionMissing('_old_input.smtp_password');
    $this->assertDatabaseCount('mail_accounts', 0);
    $this->assertDatabaseCount('mail_credentials', 0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'invalid'], 'email'],
    'URL is not a hostname' => [['imap_host' => 'https://mail.example.test/path'], 'imap_host'],
    'newline hostname' => [['smtp_host' => "smtp.example.test\r\nX:test"], 'smtp_host'],
    'invalid IMAP port' => [['imap_port' => 0], 'imap_port'],
    'oversized SMTP port' => [['smtp_port' => 65536], 'smtp_port'],
    'plaintext IMAP' => [['imap_encryption' => 'none'], 'imap_encryption'],
    'plaintext SMTP' => [['smtp_encryption' => 'none'], 'smtp_encryption'],
    'missing username' => [['imap_username' => ''], 'imap_username'],
    'missing password' => [['smtp_password' => ''], 'smtp_password'],
    'Google account requires OAuth' => [['email' => 'person@gmail.com'], 'email'],
]);

test('password whitespace is preserved exactly through validation and storage', function () {
    $this->instance(CredentialCipher::class, new FakeCredentialCipher);
    $this->post('/accounts', mailAccountInput(['imap_password' => '  significant spaces  ']))->assertSessionHasNoErrors();
    expect(app(CredentialStore::class)->get(MailAccount::query()->sole())['imap_password'])->toBe('  significant spaces  ');
});

test('browser account setup cannot bypass unavailable platform storage', function () {
    config(['nativephp-internal.running' => false]);
    $this->get('/accounts/create')->assertInertia(fn (Assert $page) => $page->where('secureStorageAvailable', false));
    $this->post('/accounts', mailAccountInput())->assertSessionHasErrors('credentials');
    $this->assertDatabaseCount('mail_accounts', 0);
    $this->assertDatabaseCount('mail_credentials', 0);
});

test('development request recording never persists account secrets', function () {
    $this->instance(CredentialCipher::class, new FakeCredentialCipher);
    $recorderStorage = $this->mock(EntriesRepository::class);
    $recorderStorage->shouldReceive('save')->andReturnNull();
    $recorderStorage->shouldReceive('pruneIfDue')->andReturnNull();
    $this->app['env'] = 'local';

    try {
        $this->withSession(['_token' => 'test-csrf-token'])
            ->withHeaders(['X-Inertia' => 'true', 'X-NativePHP-Secret' => 'test-native-secret'])
            ->post('/accounts', mailAccountInput(['_token' => 'test-csrf-token']))
            ->assertRedirect('/accounts')->assertSessionHasNoErrors();
        $recorderStorage->shouldNotHaveReceived('save');
        expect(DevTools::recorder())->toBeNull();
    } finally {
        $this->app['env'] = 'testing';
    }
});
