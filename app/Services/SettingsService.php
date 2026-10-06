<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Admin-editable site settings stored as key/value rows and cached as a
 * single array. Values are stored as JSON so arrays round-trip cleanly.
 */
class SettingsService
{
    private const CACHE_KEY = 'site_settings';

    /** Keys the admin settings screen manages, with their defaults. */
    public const DEFAULTS = [
        'site_name' => null, // falls back to config('app.name')
        'site_tagline' => 'Celebrating the creators shaping culture.',
        'seo_description' => 'Vote for your favourite nominees, follow the leaderboard and book your seat at the awards night.',
        'contact_email' => 'hello@example.com',
        'contact_phone' => null,
        'contact_address' => null,
        'social_instagram' => null,
        'social_x' => null,
        'social_tiktok' => null,
        'social_youtube' => null,
        'social_facebook' => null,
        'hero_title' => 'Your vote decides who takes the stage.',
        'hero_subtitle' => 'Back the creators you love. Cast a free vote or power up your support with a vote package.',
        'home_about' => null,
        'donation_presets' => [1000, 5000, 10000, 25000, 50000],
        'donation_min' => 100,
        'footer_text' => null,
    ];

    private ?array $loaded = null;

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')
                    ->map(fn ($v) => json_decode((string) $v, true))
                    ->all();
            });
        } catch (Throwable) {
            // Settings must never take the site down (e.g. before migrations run).
            $stored = [];
        }

        return $this->loaded = array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        if ($key === 'site_name' && blank($value)) {
            return config('app.name');
        }

        return $value ?? $default;
    }

    /** @param array<string, mixed> $values */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }
}
