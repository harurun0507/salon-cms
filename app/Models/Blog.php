<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    public const BODY_FORMAT_PLAIN = 'plain';

    protected $fillable = [
        'title',
        'slug',
        'body',
        'body_format',
        'eye_catch_image_path',
        'published_at',
        'is_published',
        'display_order',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'display_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Blog $blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title) ?: 'blog';
            }

            if (empty($blog->body_format)) {
                $blog->body_format = self::BODY_FORMAT_PLAIN;
            }
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * Publicly visible posts: published flag on and post date not in the future.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderBy('display_order')
            ->orderByDesc('id');
    }

    public function hasEyeCatch(): bool
    {
        return filled($this->eye_catch_image_path);
    }

    public function renderedBody(): string
    {
        // Plain text for now; body_format leaves room for HTML/Markdown later.
        return (string) $this->body;
    }
}
