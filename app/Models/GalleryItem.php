<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryItem extends Model
{
    public const TYPES = ['image' => 'Image', 'video' => 'Video file', 'youtube' => 'YouTube video'];

    protected $fillable = [
        'award_event_id', 'type', 'title', 'caption', 'description', 'file_path', 'thumbnail',
        'youtube_url', 'sort_order', 'status',
    ];

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public static function parseYoutubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $url, $m);

        return $m[1] ?? null;
    }

    public function youtubeId(): ?string
    {
        return self::parseYoutubeId($this->youtube_url);
    }
}
