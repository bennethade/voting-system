<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Human-friendly unique references such as VOTE-2026-7KQ2M9XA. Collisions
 * are astronomically unlikely, but each column also has a unique index and
 * we re-roll on the off chance a candidate already exists.
 */
class ReferenceGenerator
{
    /** Unambiguous characters only (no 0/O, 1/I/L). */
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /** @param class-string<Model> $model */
    public function generate(string $prefix, string $model, string $column = 'reference', int $length = 8): string
    {
        do {
            $reference = sprintf('%s-%s-%s', $prefix, now()->format('Y'), $this->random($length));
        } while ($model::query()->where($column, $reference)->exists());

        return $reference;
    }

    public function random(int $length): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    /** Gateway transaction reference for a single payment attempt. */
    public function paymentReference(): string
    {
        return 'PAY-'.now()->format('ymd').'-'.Str::upper(Str::random(4)).$this->random(10);
    }
}
