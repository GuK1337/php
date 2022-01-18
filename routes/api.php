<?php

use App\Http\Requests\FileRequest;
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
        'index', 'show', 'store', 'update', 'destroy',
        ]);
//    Route::resource('/files', 'FileController')->only([
//        'index', 'show', 'store', 'update', 'destroy',
//    ]);
//    Route::resource('/folders/{folder_id}/files', 'FolderController')->only([
//        'index', 'show', 'store', 'update', 'destroy',
//    ]);
    Route::post('/folders/{folderId}/files', 'FileController@store');
    Route::get('/folders/{folderId}/files/{fileId}', 'FileController@show');
    Route::delete('/folders/{folderId}/files/{fileId}', 'FileController@destroy');
    Route::get('/folders/{folderId}/files', 'FileController@index');
    Route::patch('/folders/{folderId}/files/{fileId}', 'FileController@edit');
    Route::post('/folders/{folderId}/files/{fileId}', 'FileController@addUser');
});

