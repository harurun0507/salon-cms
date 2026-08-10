<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

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

    public function isCombinationMenu(): bool
    {
        if ($this->relationLoaded('categories')) {
            return $this->categories->contains(
                fn (MenuCategory $category) => $category->isCombination()
            );
        }

        return $this->categories()
            ->where('menu_categories.allow_multiple_selection', true)
            ->exists();
    }

    /**
     * Linked categories shown as tags on combination menus
     * (excludes the allow_multiple / set category itself).
     *
     * @return Collection<int, MenuCategory>
     */
    public function constituentCategoriesForDisplay(): Collection
    {
        $categories = $this->relationLoaded('categories')
            ? $this->categories
            : $this->categories()->orderBy('menu_categories.sort_order')->get();

        return $categories
            ->filter(fn (MenuCategory $category) => ! $category->isCombination())
            ->sortBy('sort_order')
            ->values();
    }
}
