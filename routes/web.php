<?php

use App\Http\Controllers\CvController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoreAnalyticsEventController;
use App\Http\Middleware\TrackPortfolioPageView;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/favicon.svg', FaviconController::class)
    ->middleware('cache.headers:public;max_age=86400;etag')
    ->name('favicon');

Route::middleware(TrackPortfolioPageView::class)->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/cv', CvController::class)->name('cv.show');
});

Route::post('/analytics/events', StoreAnalyticsEventController::class)
    ->middleware('throttle:analytics')
    ->name('analytics.events.store');
