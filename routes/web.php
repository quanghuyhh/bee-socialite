<?php

use Bee\Socialite\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'guest', 'as' => 'auth.social'], function () {
    Route::get('/login/{provider}', [SocialAuthController::class, 'handleRedirect'])->name('redirect');
    Route::get('/login/{provider}/callback', [SocialAuthController::class, 'handleCallback'])->name('callback');
});
