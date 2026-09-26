<?php

namespace App\Actions\Mail;

use App\Models\MailAccount;
use App\Models\Mailbox;
use App\Models\MailMessage;
use Illuminate\Database\Eloquent\Builder;

class ReadMailbox
{
    /** @return array<string, mixed> */
    public function read(?int $accountId, ?int $mailboxId, ?int $messageId, string $view, string $search, ?string $cursor): array
    {
        $accounts = MailAccount::query()->orderBy('id')
            ->get(['id', 'name', 'email', 'provider', 'status', 'last_synced_at']);

        if ($accountId !== null) {
            abort_unless($accounts->contains('id', $accountId), 404);
        }

        $mailboxes = Mailbox::query()->whereIn('mail_account_id', $accounts->modelKeys())
            ->when($accountId !== null, fn (Builder $query) => $query->where('mail_account_id', $accountId))
            ->orderBy('name')->orderBy('id')->get(['id', 'mail_account_id', 'name', 'kind']);

        if ($mailboxId !== null) {
            abort_unless($mailboxes->contains('id', $mailboxId), 404);
        }

        $messages = MailMessage::query()->whereIn('mail_account_id', $accounts->modelKeys())
            ->when($accountId !== null, fn (Builder $query) => $query->where('mail_account_id', $accountId));

        if ($mailboxId !== null) {
            $messages->whereHas('mailboxes', fn (Builder $query) => $query->whereKey($mailboxId));
        } elseif ($view === 'inbox') {
            $messages->whereHas('mailboxes', fn (Builder $query) => $query->where('kind', 'inbox'));
        } elseif ($view === 'starred') {
            $messages->where('is_starred', true);
        }

        if ($search !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $messages->where(function (Builder $query) use ($pattern): void {
                foreach (['subject', 'from_name', 'from_email', 'snippet'] as $column) {
                    $query->orWhereRaw("LOWER({$column}) LIKE LOWER(?) ESCAPE '!'", [$pattern]);
                }
            });
        }

        $selected = null;
        if ($messageId !== null) {
            $message = (clone $messages)->withCount('attachments')->with(['body:id,mail_message_id,text', 'recipients', 'attachments'])->findOrFail($messageId);
            $selected = [
                ...$this->summary($message),
                'text' => $message->body?->text,
                'recipients' => $message->recipients->map->only(['kind', 'name', 'email']),
                'attachments' => $message->attachments->map->only(['id', 'filename', 'mime_type', 'size']),
            ];
        }

        $page = $messages->select(['id', 'mail_account_id', 'mail_thread_id', 'subject', 'from_name', 'from_email', 'snippet', 'received_at', 'is_read', 'is_starred'])
            ->withCount('attachments')->orderByDesc('received_at')->orderByDesc('id')
            ->cursorPaginate(50, ['*'], 'cursor', $cursor);

        return [
            'accounts' => $accounts,
            'mailboxes' => $mailboxes,
            'filters' => ['account' => $accountId, 'mailbox' => $mailboxId, 'view' => $view, 'q' => $search],
            'messages' => [
                'data' => $page->getCollection()->map(fn (MailMessage $message): array => $this->summary($message)),
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
            'selectedMessage' => $selected,
        ];
    }

    /** @return array<string, mixed> */
    private function summary(MailMessage $message): array
    {
        return [
            'id' => $message->id,
            'mail_account_id' => $message->mail_account_id,
            'mail_thread_id' => $message->mail_thread_id,
            'subject' => $message->subject,
            'from_name' => $message->from_name,
            'from_email' => $message->from_email,
            'snippet' => $message->snippet,
            'received_at' => $message->received_at->toIso8601String(),
            'is_read' => $message->is_read,
            'is_starred' => $message->is_starred,
            'has_attachments' => $message->attachments_count > 0,
        ];
    }
}
