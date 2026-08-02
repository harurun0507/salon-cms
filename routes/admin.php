<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\HeroImageController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\SalonSettingController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('news', NewsController::class)->except(['show']);
        Route::put('galleries/bulk', [GalleryController::class, 'bulkUpdate'])->name('galleries.bulk-update');
        Route::resource('galleries', GalleryController::class)->except(['show']);

        Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
        Route::put('menus/bulk', [MenuController::class, 'bulkUpdate'])->name('menus.bulk-update');
        Route::get('menus/categories/create', [MenuController::class, 'createCategory'])->name('menus.categories.create');
        Route::post('menus/categories', [MenuController::class, 'storeCategory'])->name('menus.categories.store');
        Route::get('menus/categories/{category}/edit', [MenuController::class, 'editCategory'])->name('menus.categories.edit');
        Route::put('menus/categories/{category}', [MenuController::class, 'updateCategory'])->name('menus.categories.update');
        Route::delete('menus/categories/{category}', [MenuController::class, 'destroyCategory'])->name('menus.categories.destroy');
        Route::get('menus/categories/{category}/menus/create', [MenuController::class, 'create'])->name('menus.create');
        Route::post('menus/categories/{category}/menus', [MenuController::class, 'store'])->name('menus.store');
        Route::get('menus/items/{menu}/edit', [MenuController::class, 'edit'])->name('menus.edit');
        Route::put('menus/items/{menu}', [MenuController::class, 'update'])->name('menus.update');
        Route::delete('menus/items/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');

        Route::resource('staff', StaffController::class)->except(['show']);
        Route::get('settings', [SalonSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SalonSettingController::class, 'update'])->name('settings.update');
        Route::delete('settings/logo', [SalonSettingController::class, 'destroyLogo'])->name('settings.logo.destroy');

        Route::get('home/hero', [HeroImageController::class, 'edit'])->name('home.hero');
        Route::put('home/hero', [HeroImageController::class, 'update'])->name('home.hero.update');
        Route::delete('home/hero/images/{heroImage}', [HeroImageController::class, 'destroy'])->name('home.hero.destroy');

        // Placeholder screens (navigation only; features not implemented yet)
        Route::view('home/top', 'admin.placeholder', ['title' => 'トップページ設定'])->name('home.top');
        Route::view('home/banners', 'admin.placeholder', ['title' => 'バナー'])->name('home.banners');
        Route::view('store/sns', 'admin.placeholder', ['title' => 'SNS'])->name('store.sns');
        Route::view('store/reservations', 'admin.placeholder', ['title' => '予約設定'])->name('store.reservations');
        Route::view('system/seo', 'admin.placeholder', ['title' => 'SEO'])->name('system.seo');
        Route::view('system/users', 'admin.placeholder', ['title' => '管理ユーザー'])->name('system.users');
        Route::view('system/design', 'admin.placeholder', ['title' => 'デザイン設定'])->name('system.design');
    });
});


