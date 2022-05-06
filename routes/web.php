<?php

use Codewiser\Folks\Http\Controllers\HomeController;
use Codewiser\Folks\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
});

// Catch-all Route...
Route::get('/{view?}', [HomeController::class, 'index'])->where('view', '(.*)')->name('index');
