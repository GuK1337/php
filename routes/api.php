<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register',[\App\Http\Controllers\Auth\RegisterController::class, 'create']);
Route::post('login',[\App\Http\Controllers\Auth\LoginController::class, 'login1']);
Route::group(['middleware' => 'auth:api'], function () {
//    Route::post('folder', [\App\Http\Controllers\FolderController::class, 'store']);
//    Route::get('folders', [\App\Http\Controllers\FolderController::class, 'index']);
//    Route::get('folder', [\App\Http\Controllers\FolderController::class, 'show']);
    Route::resource('/folders', 'FolderController')->only([
        'index', 'show', 'store', 'update', 'destroy', 'create',
        ]);
});

