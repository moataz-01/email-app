<?php

namespace App\Models;

use Database\Factories\MailAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $imap_host
 * @property int|null $imap_port
 * @property string|null $imap_encryption
 * @property string|null $imap_username
 * @property string|null $smtp_host
 * @property int|null $smtp_port
 * @property string|null $smtp_encryption
 * @property string|null $smtp_username
 */
#[Fillable(['provider', 'name', 'email', 'credential_reference', 'status', 'last_synced_at', 'imap_host', 'imap_port', 'imap_encryption', 'imap_username', 'imap_status', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_status', 'last_connection_tested_at'])]
#[Hidden(['credential_reference', 'legacy_user_id'])]
class MailAccount extends Model
{
    /** @use HasFactory<MailAccountFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'immutable_datetime',
            'last_connection_tested_at' => 'immutable_datetime',
            'imap_port' => 'integer',
            'smtp_port' => 'integer',
        ];
    }

    /** @return HasMany<Mailbox, $this> */
    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class, 'mail_account_id');
    }

    /** @return HasMany<MailMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(MailMessage::class, 'mail_account_id');
    }

    /** @return HasMany<MailThread, $this> */
    public function threads(): HasMany
    {
        return $this->hasMany(MailThread::class, 'mail_account_id');
    }

    /** @return HasMany<MailSyncState, $this> */
    public function syncStates(): HasMany
    {
        return $this->hasMany(MailSyncState::class, 'mail_account_id');
    }
}
