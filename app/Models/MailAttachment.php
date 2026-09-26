<?php

namespace App\Models;

use Database\Factories\MailAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mail_message_id', 'provider_id', 'filename', 'mime_type', 'size', 'content_id'])]
class MailAttachment extends Model
{
    /** @use HasFactory<MailAttachmentFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /** @return BelongsTo<MailMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(MailMessage::class, 'mail_message_id');
    }
}
