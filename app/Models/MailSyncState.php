<?php

namespace App\Models;

use Database\Factories\MailSyncStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mail_account_id', 'mailbox_id', 'scope', 'cursor', 'uid_validity', 'last_synced_at'])]
class MailSyncState extends Model
{
    /** @use HasFactory<MailSyncStateFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<MailAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'mail_account_id');
    }

    /** @return BelongsTo<Mailbox, $this> */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }
}
