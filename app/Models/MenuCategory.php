<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class)->orderBy('sort_order');
    }

    public function publishedMenus(): HasMany
    {
        return $this->menus()->where('is_published', true);
    }
}
