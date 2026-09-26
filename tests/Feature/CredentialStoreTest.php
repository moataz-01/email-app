<?php

use App\Contracts\CredentialCipher;
use App\Exceptions\CredentialStorageUnavailable;
use App\Models\MailAccount;
use App\Services\Credentials\CredentialStore;
use App\Services\Credentials\NativeCredentialCipher;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\FakeCredentialCipher;

test('credential rotation replaces protected values without leaving orphaned records', function () {
    $this->instance(CredentialCipher::class, new FakeCredentialCipher);
    $account = MailAccount::factory()->create();
    $store = app(CredentialStore::class);
    $store->put($account, ['refresh_token' => 'old-token']);
    $store->put($account, ['refresh_token' => 'new-token']);

    expect($store->get($account))->toBe(['refresh_token' => 'new-token']);
    $this->assertDatabaseCount('mail_credentials', 1);
    $account->delete();
    $this->assertDatabaseCount('mail_credentials', 0);
});

test('ciphertext copied from a different account is rejected', function () {
    $this->instance(CredentialCipher::class, new FakeCredentialCipher);
    $store = app(CredentialStore::class);
    $first = MailAccount::factory()->create();
    $second = MailAccount::factory()->create();
    $store->put($first, ['password' => 'first']);
    $store->put($second, ['password' => 'second']);
    DB::table('mail_credentials')->where('mail_account_id', $second->id)->update([
        'ciphertext' => DB::table('mail_credentials')->where('mail_account_id', $first->id)->value('ciphertext'),
    ]);
    expect(fn () => $store->get($second))->toThrow(CredentialStorageUnavailable::class);
});

test('credential corruption and missing credentials fail with a safe error', function () {
    $this->instance(CredentialCipher::class, new FakeCredentialCipher);
    $store = app(CredentialStore::class);
    $account = MailAccount::factory()->create();
    expect(fn () => $store->get($account))->toThrow(CredentialStorageUnavailable::class);
    $store->put($account, ['password' => 'private']);
    DB::table('mail_credentials')->update(['ciphertext' => 'corrupt']);
    expect(fn () => $store->get($account))->toThrow(CredentialStorageUnavailable::class);
});

test('native encryption fails closed outside the desktop runtime', function () {
    config(['nativephp-internal.running' => false]);
    $cipher = app(NativeCredentialCipher::class);
    expect($cipher->available())->toBeFalse();
    expect(fn () => $cipher->encrypt('private'))->toThrow(CredentialStorageUnavailable::class);
});
