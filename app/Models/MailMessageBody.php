<?php

namespace App\Models;

use Database\Factories\MailMessageBodyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mail_message_id', 'text', 'html'])]
#[Hidden(['html'])]
class MailMessageBody extends Model
{
    /** @use HasFactory<MailMessageBodyFactory> */
    use HasFactory;

    /** @return BelongsTo<MailMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(MailMessage::class, 'mail_message_id');
    }
}
