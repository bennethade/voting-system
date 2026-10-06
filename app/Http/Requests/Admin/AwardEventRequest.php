<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventStatus;
use App\Enums\FreeVoteScope;
use App\Enums\ResultsVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AwardEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('events.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash', Rule::unique('award_events', 'slug')->ignore($event?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'venue' => ['nullable', 'string', 'max:200'],
            'start_date' => ['nullable', 'date'],
            'voting_start_at' => ['nullable', 'date'],
            'voting_end_at' => ['nullable', 'date', 'after:voting_start_at'],
            'event_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'results_visibility' => ['required', Rule::enum(ResultsVisibility::class)],
            'is_featured' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600'],

            'settings.free_voting_enabled' => ['nullable', 'boolean'],
            'settings.free_vote_scope' => ['required', Rule::enum(FreeVoteScope::class)],
            'settings.free_vote_requires_verification' => ['nullable', 'boolean'],
            'settings.paid_voting_enabled' => ['nullable', 'boolean'],
            'settings.paid_vote_requires_login' => ['nullable', 'boolean'],
            'settings.booking_enabled' => ['nullable', 'boolean'],
        ];
    }

    /** Attributes safe to mass-assign onto the model (files handled separately). */
    public function eventData(): array
    {
        $data = $this->safe()->except(['logo', 'banner', 'settings']);
        $data['is_featured'] = $this->boolean('is_featured');

        $data['settings'] = [
            'free_voting_enabled' => $this->boolean('settings.free_voting_enabled'),
            'free_vote_scope' => $this->input('settings.free_vote_scope'),
            'free_vote_requires_verification' => $this->boolean('settings.free_vote_requires_verification'),
            'paid_voting_enabled' => $this->boolean('settings.paid_voting_enabled'),
            'paid_vote_requires_login' => $this->boolean('settings.paid_vote_requires_login'),
            'booking_enabled' => $this->boolean('settings.booking_enabled'),
        ];

        return $data;
    }
}
