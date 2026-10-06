<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('content.manage');
    }

    protected function prepareForValidation(): void
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        // System pages are linked from fixed routes (/about, /terms, ...), so
        // their slug never changes regardless of what was submitted.
        $slug = $page?->is_system
            ? $page->slug
            : Str::slug((string) ($this->input('slug') ?: $this->input('title')));

        $this->merge(['slug' => $slug]);
    }

    public function rules(): array
    {
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'content' => ['nullable', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:150'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in(['published', 'draft'])],
        ];
    }
}
