<?php

use App\Http\Controllers\Front\ContentController;
use App\Http\Controllers\Front\DocumentController;
use App\Http\Controllers\Front\GalleryController;
use App\Http\Controllers\Front\LegacyRedirectController;
use App\Http\Controllers\Front\SeoController;
use App\Http\Controllers\Front\SiteController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
| Public website. URLs keep the old Yii format (".html" suffix) so existing links,
| bookmarks and search-engine results keep working.
*/

Route::name('front.')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('home');

    Route::get('contact.html', [SiteController::class, 'contact'])->name('contact');
    Route::post('contact.html', [SiteController::class, 'contactSend'])->name('contact.send')->middleware('throttle:5,1');
    Route::post('feedback/post.html', [SiteController::class, 'feedback'])->name('feedback')->middleware('throttle:5,1');

    Route::get('content/{id}.html', [ContentController::class, 'view'])->whereNumber('id')->name('content.view');
    Route::get('content/index/{id}.html', [ContentController::class, 'index'])->whereNumber('id')->name('content.index');
    Route::get('document/index/{id}.html', [DocumentController::class, 'index'])->whereNumber('id')->name('document.index');
    Route::get('document/download/{id}.html', [DocumentController::class, 'download'])->whereNumber('id')->name('document.download');
    Route::get('gallery/index/{id}.html', [GalleryController::class, 'index'])->whereNumber('id')->name('gallery.index');
});

Route::get('health', HealthController::class)->name('health')->middleware('throttle:30,1');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Alternate URL shapes the Yii router also accepted.
Route::redirect('index.html', '/', 301);
Route::redirect('site/index.html', '/', 301);
Route::redirect('site/contact.html', '/contact.html', 301);
Route::get('content/view/{id}.html', [LegacyRedirectController::class, 'content'])->whereNumber('id');
Route::get('document/{id}.html', [LegacyRedirectController::class, 'document'])->whereNumber('id');
Route::redirect('admin.php', '/admin', 301);
