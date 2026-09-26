<?php

namespace App\Http\Requests;

use App\Services\Mail\ConnectionTesting\MailServerRules;
use Illuminate\Foundation\Http\FormRequest;

class TestImapConnectionRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return MailServerRules::forProtocol('imap');
    }
}
