<?php

namespace App\Http\Requests\Admin;

use App\Models\Nominee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NomineeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('nominees.manage');
    }

    protected function prepareForValidation(): void
    {
        $autoSlug = blank($this->input('slug'));
        $slug = Str::limit(Str::slug((string) ($autoSlug ? $this->input('name') : $this->input('slug'))), 150, '');

        // Only de-duplicate slugs we generated; an explicit slug that collides
        // fails validation so the admin can choose another one.
        if ($autoSlug && $slug !== '') {
            $slug = $this->uniqueSlug($slug);
        }

        $links = $this->input('social_links');

        $this->merge([
            'slug' => $slug,
            'sort_order' => $this->input('sort_order') ?? 0,
            // Keep only the supported networks; anything else is dropped.
            'social_links' => is_array($links) ? Arr::only($links, Nominee::SOCIAL_NETWORKS) : [],
        ]);
    }

    public function rules(): array
    {
        $nominee = $this->route('nominee');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash', Rule::unique('nominees', 'slug')->ignore($nominee?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'social_links' => ['nullable', 'array:'.implode(',', Nominee::SOCIAL_NETWORKS)],
            'social_links.*' => ['nullable', 'url:http,https', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function attributes(): array
    {
        return collect(Nominee::SOCIAL_NETWORKS)
            ->mapWithKeys(fn ($network) => ["social_links.$network" => ($network === 'x' ? 'X' : ucfirst($network)).' link'])
            ->all();
    }

    /**
     * Attributes safe to mass-assign onto the model. Files are handled
     * separately and total_votes is never editable here.
     */
    public function nomineeData(): array
    {
        $data = $this->safe()->except(['image', 'cover_image', 'social_links']);
        $data['social_links'] = array_filter(
            Arr::only((array) $this->validated('social_links', []), Nominee::SOCIAL_NETWORKS),
            fn ($url) => filled($url),
        );

        return $data;
    }

    private function uniqueSlug(string $base): string
    {
        $ignoreId = $this->route('nominee')?->id;
        $slug = $base;
        $i = 2;

        while (Nominee::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
