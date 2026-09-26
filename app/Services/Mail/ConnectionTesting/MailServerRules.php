<?php

namespace App\Services\Mail\ConnectionTesting;

use Illuminate\Validation\Rule;

class MailServerRules
{
    /** @return array<string, array<int, mixed>> */
    public static function forProtocol(string $protocol): array
    {
        return [
            $protocol.'_host' => ['required', 'string', 'max:253', 'regex:/\A(?=.{1,253}\z)(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\z/'],
            $protocol.'_port' => ['required', 'integer', 'between:1,65535'],
            $protocol.'_encryption' => ['required', Rule::in(['tls', 'starttls'])],
            $protocol.'_username' => ['required', 'string', 'max:255', 'not_regex:/[\r\n\x00]/'],
            $protocol.'_password' => ['required', 'string', 'max:4096', 'not_regex:/[\r\n\x00]/'],
        ];
    }
}
