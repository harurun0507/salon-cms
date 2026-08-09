<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'price',
        'description',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(MenuCategory::class, 'menu_category_menu')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('menu_categories.sort_order');
    }

    public function isInquiryPrice(): bool
    {
        $price = trim((string) $this->price);

        return $price !== '' && str_contains($price, '要問い合わせ');
    }
}
