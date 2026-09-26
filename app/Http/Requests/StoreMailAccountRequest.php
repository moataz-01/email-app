<?php

namespace App\Http\Requests;

use App\Services\Mail\ConnectionTesting\MailServerRules;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreMailAccountRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && preg_match('/@(gmail|googlemail)\.com$/i', $value)) {
                    $fail('Use Google authorization for Gmail accounts. Google passwords are not accepted.');
                }
            }],
        ];
        foreach (['imap', 'smtp'] as $protocol) {
            $rules = [...$rules, ...MailServerRules::forProtocol($protocol)];
        }

        return $rules;
    }
}
