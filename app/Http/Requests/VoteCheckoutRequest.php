<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the *choice* of package is taken from the client. Its price and vote
 * count are looked up server-side in OrderService.
 */
class VoteCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($user = $this->user()) {
            $this->merge(['email' => $user->email, 'name' => $this->input('name') ?: $user->name]);
        }
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{7,32}$/'],
        ];
    }

    public function messages(): array
    {
        return ['package_id.required' => 'Please choose a voting package.'];
    }
}
