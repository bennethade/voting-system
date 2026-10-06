<?php

use App\Services\SettingsService;
use Illuminate\Support\Facades\Storage;

if (! function_exists('money')) {
    /** Format an amount in a currency's major unit, e.g. money(1000) => "₦1,000". */
    function money(string|int|float|null $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? config('awards.currency'));
        $symbol = config("awards.currency_symbols.$currency", $currency.' ');
        $amount = (float) $amount;
        $decimals = floor($amount) == $amount ? 0 : 2;

        return $symbol.number_format($amount, $decimals);
    }
}

if (! function_exists('to_subunit')) {
    /**
     * Convert a decimal major-unit amount ("1000.50") to an integer subunit
     * (100050) using string arithmetic so no float rounding can creep in.
     */
    function to_subunit(string|int|float $amount): int
    {
        $amount = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $amount);

        return ((int) $whole) * 100 + (int) $fraction * ($whole[0] === '-' ? -1 : 1);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('media_url')) {
    /** Public URL of a stored upload, or of a full external URL, or a fallback. */
    function media_url(?string $path, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return strtoupper(collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }
}
