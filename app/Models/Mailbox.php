<?php

namespace App\Models;

use Database\Factories\MailboxFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['mail_account_id', 'provider_id', 'name', 'kind'])]
class Mailbox extends Model
{
    /** @use HasFactory<MailboxFactory> */
    use HasFactory;

    /** @return BelongsTo<MailAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'mail_account_id');
    }

    /** @return BelongsToMany<MailMessage, $this> */
    public function messages(): BelongsToMany
    {
        return $this->belongsToMany(MailMessage::class, 'mailbox_messages')->withPivot(['mail_account_id', 'uid', 'uid_validity']);
    }
}
