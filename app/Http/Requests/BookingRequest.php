<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($user = $this->user()) {
            $this->merge([
                'name' => $this->input('name') ?: $user->name,
                'email' => $this->input('email') ?: $user->email,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{7,32}$/'],
            'organization' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attendees' => ['nullable', 'array', 'max:200'],
            'attendees.*.name' => ['nullable', 'string', 'max:120'],
            'attendees.*.email' => ['nullable', 'email:rfc', 'max:255'],
        ];
    }
}
