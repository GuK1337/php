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

Route::post('registration',[\App\Http\Controllers\Auth\RegisterController::class, 'create']);
Route::post('authorization',[\App\Http\Controllers\Auth\LoginController::class, 'login']);
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
    Route::put('/folders/{folderId}/files/{fileId}/accesses', 'FileController@addUser');
    Route::delete('/folders/{folderId}/files/{fileId}/accesses', 'FileController@removeUser');
    Route::put('/folders/{folderId}/accesses', 'FolderController@addUser');
    Route::delete('/folders/{folderId}/accesses', 'FolderController@addUser');
    Route::get('/disk', 'FolderController@getAllUserFiles');
    Route::get('/shared', 'FolderController@getAllFiles');
    Route::get('login',[\App\Http\Controllers\Auth\LoginController::class, 'logout']);
});

