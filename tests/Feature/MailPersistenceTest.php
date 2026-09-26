<?php

use App\Models\MailAccount;
use App\Models\MailAttachment;
use App\Models\Mailbox;
use App\Models\MailMessage;
use App\Models\MailThread;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('remote message identities are unique within an account but reusable across accounts', function () {
    $first = MailMessage::factory()->create(['provider_id' => 'remote-1']);
    $other = MailMessage::factory()->create(['provider_id' => 'remote-1']);

    expect($other->mail_account_id)->not->toBe($first->mail_account_id);

    expect(fn () => MailMessage::factory()->for($first->account, 'account')->create(['provider_id' => 'remote-1']))
        ->toThrow(QueryException::class);
});

test('messages may have missing or repeated RFC message ids without being merged', function () {
    $account = MailAccount::factory()->create();
    MailMessage::factory()->for($account, 'account')->count(2)->create(['message_id_header' => '<same@example.test>']);
    MailMessage::factory()->for($account, 'account')->create(['message_id_header' => null]);

    expect($account->messages()->count())->toBe(3);
});

test('a message cannot reference a thread belonging to another account', function () {
    $thread = MailThread::factory()->create();

    expect(fn () => MailMessage::factory()->create(['mail_thread_id' => $thread->id]))
        ->toThrow(QueryException::class);
});

test('mailbox membership rejects a message from another account even through a direct database write', function () {
    $mailbox = Mailbox::factory()->create();
    $message = MailMessage::factory()->create();

    expect(fn () => DB::table('mailbox_messages')->insert([
        'mail_account_id' => $mailbox->mail_account_id,
        'mailbox_id' => $mailbox->id,
        'mail_message_id' => $message->id,
    ]))->toThrow(QueryException::class);
});

test('IMAP UIDs are unique inside a mailbox epoch and can be reused in a new epoch', function () {
    $mailbox = Mailbox::factory()->create();
    $messages = MailMessage::factory()->for($mailbox->account, 'account')->count(3)->create();
    $mailbox->messages()->attach($messages[0], ['mail_account_id' => $mailbox->mail_account_id, 'uid' => '42', 'uid_validity' => '100']);
    $mailbox->messages()->attach($messages[1], ['mail_account_id' => $mailbox->mail_account_id, 'uid' => '42', 'uid_validity' => '101']);

    expect($mailbox->messages()->count())->toBe(2);
    expect(fn () => $mailbox->messages()->attach($messages[2], ['mail_account_id' => $mailbox->mail_account_id, 'uid' => '42', 'uid_validity' => '100']))
        ->toThrow(QueryException::class);
});

test('one cached message may belong to multiple labels without duplicating its body', function () {
    $message = MailMessage::factory()->create();
    $mailboxes = Mailbox::factory()->for($message->account, 'account')->count(2)->create();
    foreach ($mailboxes as $mailbox) {
        $mailbox->messages()->attach($message, ['mail_account_id' => $message->mail_account_id]);
    }
    $message->body()->create(['text' => 'Hello', 'html' => '<script>unsafe()</script>']);

    expect($message->mailboxes()->count())->toBe(2);
    expect($message->body->text)->toBe('Hello');
    expect($message->body->toArray())->not->toHaveKey('html');
    expect($message->fresh()->toArray())->not->toHaveKey('body');
});

test('sync cursors preserve large opaque values and reject cross-account mailbox references', function () {
    $mailbox = Mailbox::factory()->create();
    $state = $mailbox->account->syncStates()->create([
        'scope' => 'mailbox:'.$mailbox->id,
        'mailbox_id' => $mailbox->id,
        'cursor' => '18446744073709551615',
        'uid_validity' => '4294967295',
    ]);

    expect($state->fresh()->cursor)->toBe('18446744073709551615');
    expect($state->fresh()->uid_validity)->toBe('4294967295');
    expect(fn () => MailAccount::factory()->create()->syncStates()->create([
        'scope' => 'mailbox:'.$mailbox->id, 'mailbox_id' => $mailbox->id,
    ]))->toThrow(QueryException::class);
});

test('removing an account clears its cached content and leaves other accounts intact', function () {
    $message = MailMessage::factory()->create();
    $other = MailMessage::factory()->create();
    $mailbox = Mailbox::factory()->for($message->account, 'account')->create();
    $mailbox->messages()->attach($message, ['mail_account_id' => $message->mail_account_id]);
    $message->body()->create(['text' => 'Private content']);
    $message->recipients()->create(['kind' => 'to', 'email' => 'person@example.test']);
    $message->attachments()->create(['provider_id' => 'part-1', 'filename' => 'notes.pdf', 'mime_type' => 'application/pdf', 'size' => 1024]);
    $message->account->syncStates()->create(['scope' => 'account']);
    $message->account->delete();

    $this->assertModelExists($other);
    $this->assertModelMissing($message);
    foreach (['mailboxes', 'mailbox_messages', 'mail_message_bodies', 'mail_recipients', 'mail_attachments', 'mail_sync_states'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
});

test('credential references are not exposed by account serialization', function () {
    $account = MailAccount::factory()->create(['credential_reference' => 'opaque-reference']);

    expect($account->toArray())->not->toHaveKey('credential_reference');
});

test('opaque provider identities preserve case and trailing spaces', function (string $model, string $relation) {
    $first = $model::factory()->create(['provider_id' => 'Remote-A']);
    $second = $model::factory()->for($first->{$relation}, $relation)->create(['provider_id' => 'remote-a']);
    $third = $model::factory()->for($first->{$relation}, $relation)->create(['provider_id' => 'Remote-A ']);

    $this->assertModelExists($first);
    expect($second->fresh()->provider_id)->toBe('remote-a');
    expect($third->fresh()->provider_id)->toBe('Remote-A ');
})->with([
    'message' => [MailMessage::class, 'account'],
    'mailbox' => [Mailbox::class, 'account'],
    'thread' => [MailThread::class, 'account'],
    'attachment' => [MailAttachment::class, 'message'],
]);
