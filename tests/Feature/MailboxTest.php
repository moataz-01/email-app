<?php

use App\Models\MailAccount;
use App\Models\Mailbox;
use App\Models\MailMessage;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function cachedInboxMessage(array $attributes = []): MailMessage
{
    $account = MailAccount::factory()->create();
    $mailbox = Mailbox::factory()->for($account, 'account')->create();
    $message = MailMessage::factory()->for($account, 'account')->create($attributes);
    $mailbox->messages()->attach($message, ['mail_account_id' => $account->id]);

    return $message;
}

test('first launch shows an empty cache without making remote requests', function () {
    Http::preventStrayRequests();
    $this->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('mail/index')
            ->has('accounts', 0)->has('messages.data', 0)->where('selectedMessage', null));
});

test('unified inbox includes all local accounts and excludes other folders', function () {
    $message = cachedInboxMessage();
    cachedInboxMessage();
    MailMessage::factory()->for($message->account, 'account')->create();
    $message->body()->create(['text' => 'Body must not be listed', 'html' => '<script>alert(1)</script>']);

    $this->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('accounts', 2)->has('messages.data', 2)
            ->missing('messages.data.0.body')
            ->missing('messages.data.0.provider_id')->missing('accounts.0.credential_reference'));
});

test('an account filter only returns that accounts messages', function () {
    $first = cachedInboxMessage();
    cachedInboxMessage();

    $this->get(route('dashboard', ['account' => $first->mail_account_id]))
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', 1)->where('messages.data.0.id', $first->id));
});

test('unknown account mailbox and message lookups return not found', function (string $filter) {
    cachedInboxMessage();
    $this->get(route('dashboard', [$filter => 999999]))->assertNotFound();
})->with(['account', 'mailbox', 'message']);

test('mailbox and account filters cannot be mixed across accounts', function () {
    $first = cachedInboxMessage();
    $second = cachedInboxMessage();

    $this->get(route('dashboard', [
        'account' => $first->mail_account_id, 'mailbox' => $second->mailboxes()->first()->id,
    ]))->assertNotFound();
});

test('reading exposes only the selected cached plain text and attachment metadata', function () {
    $message = cachedInboxMessage();
    $message->body()->create(['text' => '<script>shown as text</script>', 'html' => '<img src="https://tracker.test/pixel">']);
    $message->attachments()->create(['provider_id' => 'p1', 'filename' => '../../untrusted.pdf', 'mime_type' => 'application/pdf', 'size' => 3]);

    $this->get(route('dashboard', ['message' => $message->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('selectedMessage.text', '<script>shown as text</script>')
            ->missing('selectedMessage.html')->has('selectedMessage.attachments', 1)
            ->missing('selectedMessage.attachments.0.provider_id'));
    expect($message->fresh()->is_read)->toBeFalse();
});

test('search stays inside the selected scope and matches sender or subject', function () {
    $match = cachedInboxMessage(['subject' => 'Quarterly review']);
    cachedInboxMessage(['subject' => 'Lunch plans', 'from_name' => 'Alex', 'from_email' => 'alex@example.test', 'snippet' => 'Lunch']);
    cachedInboxMessage(['subject' => 'Quarterly review']);

    $this->get(route('dashboard', ['q' => 'Quarterly', 'account' => $match->mail_account_id]))
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', 1)->where('messages.data.0.id', $match->id));
});

test('message pages are bounded and stable when dates are equal', function () {
    $first = cachedInboxMessage(['received_at' => '2026-09-01 12:00:00']);
    $mailbox = $first->mailboxes()->first();
    $messages = MailMessage::factory()->for($first->account, 'account')->count(50)->create(['received_at' => $first->received_at]);
    foreach ($messages as $message) {
        $mailbox->messages()->attach($message, ['mail_account_id' => $first->mail_account_id]);
    }

    $response = $this->get(route('dashboard'));
    $response->assertInertia(fn (Assert $page) => $page->has('messages.data', 50)
        ->where('messages.data.0.id', $messages->last()->id));
    $cursor = $response->inertiaProps('messages.next_cursor');
    $secondPage = $this->get(route('dashboard', ['cursor' => $cursor]));
    $secondPage
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', 1)
            ->where('messages.data.0.id', $first->id)->where('messages.next_cursor', null));
    $this->get(route('dashboard', ['cursor' => $secondPage->inertiaProps('messages.prev_cursor')]))
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', 50)
            ->where('messages.data.0.id', $messages->last()->id)->where('messages.prev_cursor', null));
});

test('starred and all mail views include messages outside the inbox from all local accounts', function (string $view, int $count) {
    $inbox = cachedInboxMessage(['is_starred' => false]);
    MailMessage::factory()->for($inbox->account, 'account')->create(['is_starred' => true]);
    cachedInboxMessage(['is_starred' => true]);

    $this->get(route('dashboard', ['view' => $view]))
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', $count));
})->with(['starred' => ['starred', 2], 'all' => ['all', 3]]);

test('invalid mailbox filters are rejected', function (array $query, string $field) {
    $this->get(route('dashboard', $query))
        ->assertSessionHasErrors($field);
})->with([
    'bad account' => [['account' => 'no'], 'account'],
    'unknown view' => [['view' => 'invented'], 'view'],
    'oversized search' => [['q' => str_repeat('a', 201)], 'q'],
    'invalid cursor encoding' => [['cursor' => 'invalid'], 'cursor'],
    'cursor missing sort parameters' => [['cursor' => base64_encode('{"_pointsToNextItems":true}')], 'cursor'],
    'cursor non-scalar sort value' => [['cursor' => base64_encode('{"_pointsToNextItems":true,"received_at":[],"id":1}')], 'cursor'],
]);

test('search treats SQL wildcards as literal text', function () {
    $match = cachedInboxMessage(['subject' => 'Budget is 100% approved']);
    cachedInboxMessage(['subject' => 'Budget is 1000 approved']);

    $this->get(route('dashboard', ['q' => '100%']))
        ->assertInertia(fn (Assert $page) => $page->has('messages.data', 1)->where('messages.data.0.id', $match->id));
});

test('a selected message must belong to the current mailbox', function () {
    $first = cachedInboxMessage();
    $second = cachedInboxMessage();

    $this->get(route('dashboard', [
        'mailbox' => $first->mailboxes()->first()->id, 'message' => $second->id,
    ]))->assertNotFound();
});
