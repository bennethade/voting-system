<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VotingPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('packages.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'award_event_id' => ['required', 'integer', 'exists:award_events,id'],
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000000'],
            'votes' => ['required', 'integer', 'min:1', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /** Attributes safe to mass-assign onto the model. */
    public function packageData(): array
    {
        return $this->safe()->all();
    }
}
