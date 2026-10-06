<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('categories.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'award_event_id' => ['required', 'integer', 'exists:award_events,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required', 'string', 'max:160', 'alpha_dash',
                // Category slugs are unique per event (DB index: award_event_id + slug).
                Rule::unique('categories', 'slug')
                    ->where('award_event_id', (int) $this->input('award_event_id'))
                    ->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Another category in this event already uses this slug.',
        ];
    }

    /** Attributes safe to mass-assign onto the model (the image is handled separately). */
    public function categoryData(): array
    {
        return $this->safe()->except(['image']);
    }
}
