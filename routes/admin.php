<?php

use App\Admin\ResourceRegistry;
use App\Http\Controllers\Admin\AccessController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

// Loaded with prefix "admin" and name prefix "admin." (see bootstrap/app.php).

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:30,1');

    Route::get('forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:10,1');

    Route::get('two-factor', [TwoFactorChallengeController::class, 'show'])->name('two-factor');
    Route::post('two-factor', [TwoFactorChallengeController::class, 'verify'])->middleware('throttle:20,1');
});

Route::middleware(['admin.auth', 'admin.log'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('account', [AccountController::class, 'edit'])->name('account');
    Route::put('account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::put('account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::post('account/two-factor', [AccountController::class, 'startTwoFactor'])->name('account.two-factor.start');
    Route::post('account/two-factor/confirm', [AccountController::class, 'confirmTwoFactor'])->name('account.two-factor.confirm');
    Route::post('account/two-factor/recovery-codes', [AccountController::class, 'regenerateRecoveryCodes'])->name('account.two-factor.recovery');
    Route::delete('account/two-factor', [AccountController::class, 'disableTwoFactor'])->name('account.two-factor.disable');

    Route::middleware('admin.can')->group(function () {
        Route::get('settings', [SettingController::class, 'edit'])->name('settings')
            ->defaults('acl', 'setting')->defaults('acl_action', 'admin');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update')
            ->defaults('acl', 'setting')->defaults('acl_action', 'update');

        Route::post('editor-upload', EditorUploadController::class)->name('editor-upload')
            ->defaults('acl', 'content')->defaults('acl_action', 'create');

        Route::get('user-group/{id}/access', [AccessController::class, 'edit'])->name('access.edit')
            ->defaults('acl', 'userGroup')->defaults('acl_action', 'update');
        Route::put('user-group/{id}/access', [AccessController::class, 'update'])->name('access.update')
            ->defaults('acl', 'userGroup')->defaults('acl_action', 'update');

        Route::prefix('{resource}')
            ->where(['resource' => implode('|', array_keys(ResourceRegistry::all()))])
            ->group(function () {
                Route::get('/', [ResourceController::class, 'index'])->name('resource.index');
                Route::get('create', [ResourceController::class, 'create'])->name('resource.create');
                Route::post('/', [ResourceController::class, 'store'])->name('resource.store');
                Route::get('{id}/edit', [ResourceController::class, 'edit'])->whereNumber('id')->name('resource.edit');
                Route::put('{id}', [ResourceController::class, 'update'])->whereNumber('id')->name('resource.update');
                Route::delete('{id}', [ResourceController::class, 'destroy'])->whereNumber('id')->name('resource.destroy');
                Route::post('{id}/restore', [ResourceController::class, 'restore'])->whereNumber('id')->name('resource.restore');
            });
    });
});
