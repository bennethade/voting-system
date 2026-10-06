<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Validator;
use Throwable;

/**
 * Spam protection: rate limiting (route), a honeypot field, and a minimum
 * time-to-submit check using an encrypted timestamp rendered with the form.
 */
class ContactRequest extends FormRequest
{
    private const MIN_SECONDS = 3;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{7,32}$/'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'company' => ['prohibited'], // honeypot
            'form_token' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                try {
                    $rendered = (int) Crypt::decryptString((string) $this->input('form_token'));
                } catch (Throwable) {
                    $rendered = 0;
                }

                if ($rendered === 0 || time() - $rendered < self::MIN_SECONDS) {
                    $validator->errors()->add('message', 'Your message could not be sent. Please try again.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'company.prohibited' => 'Your message could not be sent.',
            'form_token.required' => 'Your message could not be sent. Please reload the page and try again.',
        ];
    }
}
