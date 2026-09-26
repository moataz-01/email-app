<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MailboxRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'account' => ['nullable', 'integer', 'min:1'],
            'mailbox' => ['nullable', 'integer', 'min:1'],
            'message' => ['nullable', 'integer', 'min:1'],
            'view' => ['nullable', Rule::in(['inbox', 'starred', 'all'])],
            'q' => ['nullable', 'string', 'max:200'],
            'cursor' => ['bail', 'nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                $cursor = Cursor::fromEncoded($value);
                if ($cursor === null || Validator::make($cursor->toArray(), [
                    'received_at' => ['required', 'string', 'date'],
                    'id' => ['required', 'integer', 'min:1'],
                    '_pointsToNextItems' => ['required', 'boolean'],
                ])->fails()) {
                    $fail('This message page is invalid. Return to the first page.');
                }
            }],
        ];
    }
}
