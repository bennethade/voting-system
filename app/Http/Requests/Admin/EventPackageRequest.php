<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EventPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('booking_packages.manage');
    }

    /**
     * The form posts benefits as `benefits_text` (one per line) so that a
     * failed submission can repopulate the textarea with the raw string.
     */
    protected function prepareForValidation(): void
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->input('benefits_text', '')) ?: [];

        $this->merge([
            'benefits' => array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== '')),
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'award_event_id' => ['required', 'integer', 'exists:award_events,id'],
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000000'],
            'seats' => ['required', 'integer', 'min:1', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'benefits' => ['array', 'max:30'],
            'benefits.*' => ['string', 'max:255'],
            'quantity_available' => ['required', 'integer', 'min:0', 'max:100000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'benefits.max' => 'List at most 30 benefits.',
            'benefits.*.max' => 'Each benefit must be 255 characters or fewer.',
        ];
    }

    /** quantity_available can never drop below what has already been sold. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $package = $this->route('eventPackage');

                if ($package && ! $validator->errors()->has('quantity_available')
                    && (int) $this->input('quantity_available') < $package->quantity_sold) {
                    $validator->errors()->add(
                        'quantity_available',
                        "Quantity available cannot be less than the {$package->quantity_sold} already sold.",
                    );
                }
            },
        ];
    }

    /** Attributes safe to mass-assign onto the model (quantity_sold is never included). */
    public function packageData(): array
    {
        return $this->safe()->all();
    }
}
