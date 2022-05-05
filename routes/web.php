<?php

use Codewiser\Folks\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {

});

// Catch-all Route...
Route::get('/{view?}', [HomeController::class, 'index'])->where('view', '(.*)')->name('folks.index');
