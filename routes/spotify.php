<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

Route::get('connect', [OAuthController::class, 'connect'])->name('spotify.connect');
Route::get('callback', [OAuthController::class, 'callback'])->name('spotify.callback');
Route::post('disconnect', [OAuthController::class, 'disconnect'])->name('spotify.disconnect');
