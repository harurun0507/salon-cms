<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuCategory extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    /**
     * Optional English label for common Japanese category names.
     * Returns null when no mapping exists (no DB field required).
     */
    public function englishName(): ?string
    {
        $map = [
            'カット' => 'CUT',
            'カラー' => 'COLOR',
            'パーマ' => 'PERM',
            'ストレート' => 'STRAIGHT',
            'トリートメント' => 'TREATMENT',
            'ヘッドスパ' => 'HEAD SPA',
            'その他' => 'OTHER',
        ];

        $name = trim((string) $this->name);

        return $map[$name] ?? null;
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_category_menu')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('menus.id');
    }

    public function publishedMenus(): BelongsToMany
    {
        return $this->menus()->where('menus.is_published', true);
    }
}
