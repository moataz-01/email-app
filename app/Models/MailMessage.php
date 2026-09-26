<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MailMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property CarbonImmutable $received_at
 * @property int $attachments_count
 */
#[Fillable(['mail_account_id', 'mail_thread_id', 'provider_id', 'message_id_header', 'subject', 'from_name', 'from_email', 'snippet', 'received_at', 'is_read', 'is_starred'])]
class MailMessage extends Model
{
    /** @use HasFactory<MailMessageFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'received_at' => 'immutable_datetime',
            'is_read' => 'boolean',
            'is_starred' => 'boolean',
        ];
    }

    /** @return BelongsTo<MailAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'mail_account_id');
    }

    /** @return BelongsTo<MailThread, $this> */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MailThread::class, 'mail_thread_id');
    }

    /** @return HasOne<MailMessageBody, $this> */
    public function body(): HasOne
    {
        return $this->hasOne(MailMessageBody::class, 'mail_message_id');
    }

    /** @return HasMany<MailRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(MailRecipient::class, 'mail_message_id');
    }

    /** @return HasMany<MailAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(MailAttachment::class, 'mail_message_id');
    }

    /** @return BelongsToMany<Mailbox, $this> */
    public function mailboxes(): BelongsToMany
    {
        return $this->belongsToMany(Mailbox::class, 'mailbox_messages')->withPivot(['mail_account_id', 'uid', 'uid_validity']);
    }
}
