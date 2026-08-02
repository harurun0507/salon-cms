<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AnalyticsSettingController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\HeroImageController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\ReservationSettingController;
use App\Http\Controllers\Admin\SalonSettingController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\SnsSettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TopPageSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('news', [NewsController::class, 'index'])->name('news.index');
    Route::put('news', [NewsController::class, 'update'])->name('news.update');
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

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::put('staff/bulk', [StaffController::class, 'bulkUpdate'])->name('staff.bulk-update');

    Route::get('settings', [SalonSettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SalonSettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/logo', [SalonSettingController::class, 'destroyLogo'])->name('settings.logo.destroy');

    Route::get('home/hero', [HeroImageController::class, 'edit'])->name('home.hero');
    Route::put('home/hero', [HeroImageController::class, 'update'])->name('home.hero.update');
    Route::delete('home/hero/images/{heroImage}', [HeroImageController::class, 'destroy'])->name('home.hero.destroy');

    Route::get('home/top', [TopPageSettingController::class, 'edit'])->name('home.top');
    Route::put('home/top', [TopPageSettingController::class, 'update'])->name('home.top.update');

    Route::get('home/banners', [BannerController::class, 'edit'])->name('home.banners');
    Route::put('home/banners', [BannerController::class, 'update'])->name('home.banners.update');

    Route::get('store/sns', [SnsSettingController::class, 'edit'])->name('store.sns');
    Route::put('store/sns', [SnsSettingController::class, 'update'])->name('store.sns.update');

    Route::get('store/reservations', [ReservationSettingController::class, 'edit'])->name('store.reservations');
    Route::put('store/reservations', [ReservationSettingController::class, 'update'])->name('store.reservations.update');

    Route::middleware('role:admin')->group(function () {
        Route::get('system/seo', [SeoSettingController::class, 'edit'])->name('system.seo');
        Route::put('system/seo', [SeoSettingController::class, 'update'])->name('system.seo.update');

        Route::get('system/analytics', [AnalyticsSettingController::class, 'edit'])->name('system.analytics');
        Route::put('system/analytics', [AnalyticsSettingController::class, 'update'])->name('system.analytics.update');

        Route::get('system/users', [AdminUserController::class, 'index'])->name('system.users');
        Route::put('system/users', [AdminUserController::class, 'bulkUpdate'])->name('system.users.update');

        Route::view('system/design', 'admin.placeholder', ['title' => 'デザイン設定'])->name('system.design');
    });
});
