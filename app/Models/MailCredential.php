<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'mail_account_id', 'ciphertext'])]
#[Hidden(['ciphertext'])]
class MailCredential extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
}
