<?php

use App\Http\Controllers\AccessPageController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\GalleryPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuPageController;
use App\Http\Controllers\NewsPageController;
use App\Http\Controllers\PrivacyPageController;
use App\Http\Controllers\RobotsTxtController;
use App\Http\Controllers\SitemapXmlController;
use App\Http\Controllers\StaffPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('guest')->group(function () {
            Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
            Route::post('login', [AuthenticatedSessionController::class, 'store']);
        });

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth')
            ->name('logout');
    });
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/menu', [MenuPageController::class, 'index'])->name('menu');
Route::get('/staff', [StaffPageController::class, 'index'])->name('staff');
Route::get('/gallery', [GalleryPageController::class, 'index'])->name('gallery');
Route::get('/access', [AccessPageController::class, 'index'])->name('access');
Route::get('/news', [NewsPageController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsPageController::class, 'show'])->name('news.show');
Route::get('/privacy', [PrivacyPageController::class, 'index'])->name('privacy');
Route::get('/robots.txt', RobotsTxtController::class)->name('robots');
Route::get('/sitemap.xml', SitemapXmlController::class)->name('sitemap');
