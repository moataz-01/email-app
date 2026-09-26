<?php

namespace App\Models;

use Database\Factories\MailRecipientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mail_message_id', 'kind', 'name', 'email'])]
class MailRecipient extends Model
{
    /** @use HasFactory<MailRecipientFactory> */
    use HasFactory;

    /** @return BelongsTo<MailMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(MailMessage::class, 'mail_message_id');
    }
}
