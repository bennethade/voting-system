<?php

namespace App\Http\Requests\Admin;

use App\Models\GalleryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GalleryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gallery.manage');
    }

    public function rules(): array
    {
        /** @var GalleryItem|null $item */
        $item = $this->route('galleryItem');
        $type = $this->input('type');

        // A file is required when creating, or when an existing item is switched
        // to a file type it does not hold a file for yet.
        $needsFile = ! $item || $item->type !== $type || blank($item->file_path);

        return [
            'award_event_id' => ['nullable', 'integer', Rule::exists('award_events', 'id')],
            'type' => ['required', Rule::in(array_keys(GalleryItem::TYPES))],
            'title' => ['required', 'string', 'max:150'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],

            'image' => [Rule::requiredIf($type === 'image' && $needsFile), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'video' => [Rule::requiredIf($type === 'video' && $needsFile), 'nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:51200'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'youtube_url' => [Rule::requiredIf($type === 'youtube'), 'nullable', 'string', 'max:255', 'url:http,https'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('type') === 'youtube'
                    && filled($this->input('youtube_url'))
                    && ! $validator->errors()->has('youtube_url')
                    && GalleryItem::parseYoutubeId($this->input('youtube_url')) === null) {
                    $validator->errors()->add('youtube_url', 'Enter a valid YouTube video link (youtube.com/watch?v=..., youtu.be/... or youtube.com/shorts/...).');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Upload an image for an image item.',
            'video.required' => 'Upload a video file for a video item.',
            'youtube_url.required' => 'Paste the YouTube link for a YouTube item.',
        ];
    }

    /** Attributes safe to mass-assign onto the model (files handled separately). */
    public function itemData(): array
    {
        $data = $this->safe()->except(['image', 'video', 'thumbnail']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['youtube_url'] = $data['type'] === 'youtube' ? ($data['youtube_url'] ?? null) : null;

        return $data;
    }
}
