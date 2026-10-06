<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // A preset button and the custom field share one "amount" value; strip
        // thousands separators people type ("5,000").
        $this->merge([
            'amount' => str_replace([',', ' '], '', (string) ($this->input('custom_amount') ?: $this->input('amount'))),
        ]);

        if ($user = $this->user()) {
            $this->merge(['email' => $user->email, 'name' => $this->input('name') ?: $user->name]);
        }
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:'.(float) setting('donation_min', 100), 'max:100000000', 'decimal:0,2'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            'anonymous' => ['nullable', 'boolean'],
        ];
    }
}
