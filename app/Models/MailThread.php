<?php

namespace App\Models;

use Database\Factories\MailThreadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['mail_account_id', 'provider_id', 'subject'])]
class MailThread extends Model
{
    /** @use HasFactory<MailThreadFactory> */
    use HasFactory;

    /** @return BelongsTo<MailAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'mail_account_id');
    }

    /** @return HasMany<MailMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(MailMessage::class, 'mail_thread_id');
    }
}
