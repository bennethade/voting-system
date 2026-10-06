<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SettingRequest extends FormRequest
{
    private const MAX_PRESETS = 8;

    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        $url = ['nullable', 'string', 'max:255', 'url:http,https'];

        return [
            // General
            'site_name' => ['nullable', 'string', 'max:100'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
            'footer_text' => ['nullable', 'string', 'max:500'],

            // SEO
            'seo_description' => ['nullable', 'string', 'max:300'],

            // Contact
            'contact_email' => ['nullable', 'string', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_address' => ['nullable', 'string', 'max:300'],

            // Social
            'social_instagram' => $url,
            'social_x' => $url,
            'social_tiktok' => $url,
            'social_youtube' => $url,
            'social_facebook' => $url,

            // Home page
            'hero_title' => ['nullable', 'string', 'max:150'],
            'hero_subtitle' => ['nullable', 'string', 'max:300'],
            'home_about' => ['nullable', 'string', 'max:5000'],

            // Donations
            'donation_presets' => ['required', 'string', 'max:200'],
            'donation_min' => ['required', 'numeric', 'min:1', 'max:100000000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('donation_presets')) {
                    return;
                }

                $parts = $this->presetParts();

                if ($parts === []) {
                    $validator->errors()->add('donation_presets', 'Enter at least one preset amount.');

                    return;
                }

                if (count($parts) > self::MAX_PRESETS) {
                    $validator->errors()->add('donation_presets', 'Enter no more than '.self::MAX_PRESETS.' preset amounts.');

                    return;
                }

                foreach ($parts as $part) {
                    if (! is_numeric($part) || (float) $part <= 0 || (float) $part > 100000000) {
                        $validator->errors()->add('donation_presets', "\"{$part}\" is not a valid positive amount.");

                        return;
                    }
                }

                $min = $this->input('donation_min');
                if (is_numeric($min) && min(array_map('floatval', $parts)) < (float) $min) {
                    $validator->errors()->add('donation_presets', 'Preset amounts cannot be lower than the minimum donation.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'social_x' => 'X (Twitter) URL',
            'social_instagram' => 'Instagram URL',
            'social_tiktok' => 'TikTok URL',
            'social_youtube' => 'YouTube URL',
            'social_facebook' => 'Facebook URL',
            'donation_min' => 'minimum donation',
        ];
    }

    /**
     * Validated values keyed exactly like SettingsService::DEFAULTS, with
     * blank strings stored as null (which falls back to the default).
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $data = collect($this->validated())
            ->except(['donation_presets', 'donation_min'])
            ->map(fn ($value) => is_string($value) && trim($value) === '' ? null : (is_string($value) ? trim($value) : $value))
            ->all();

        $data['donation_presets'] = collect($this->presetParts())
            ->map(fn ($v) => $this->number($v))
            ->unique()
            ->sort()
            ->values()
            ->all();
        $data['donation_min'] = $this->number($this->validated('donation_min'));

        return $data;
    }

    /** @return array<int, string> */
    private function presetParts(): array
    {
        return collect(explode(',', (string) $this->input('donation_presets')))
            ->map(fn ($v) => trim(str_replace([' ', "\u{20A6}"], '', $v)))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();
    }

    private function number(mixed $value): int|float
    {
        $float = (float) $value;

        return floor($float) == $float ? (int) $float : round($float, 2);
    }
}
