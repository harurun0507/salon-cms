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

    /**
     * Payload for the public in-page blog modal.
     *
     * @return array{
     *     id: int,
     *     slug: string,
     *     url: string,
     *     title: string,
     *     date: ?string,
     *     eyeCatch: ?string,
     *     body: string
     * }
     */
    public function toPublicModalData(): array
    {
        return [
            'id' => (int) $this->id,
            'slug' => (string) $this->slug,
            'url' => route('blog.show', $this->slug),
            'title' => (string) $this->title,
            'date' => $this->published_at?->format('Y年n月j日'),
            'eyeCatch' => $this->hasEyeCatch()
                ? asset('storage/'.$this->eye_catch_image_path)
                : null,
            'body' => $this->renderedBody(),
        ];
    }
}
