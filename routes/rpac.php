<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes for RPac Package
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('/admin-rpac/{vue?}', function () {
    return view('rpac::rpac');
//})->middleware(['web', 'auth'])->name('rpac')->where('vue', '[a-z0-9_\.\-\/]*');
})->middleware(['web', 'auth.basic'])->name('rpac')->where('vue', '[a-z0-9_\.\-\/]*');

Route::namespace('\Codewiser\Rpac\Controllers')->prefix('rpac')->middleware(['api', 'auth:api', 'role:su|admin'])->group(function () {
    Route::get('access', 'AccessController@index');
    Route::resource('users', 'UserController');
    Route::resource('roles', 'RoleController');
    Route::resource('permissions', 'PermissionController');
});
