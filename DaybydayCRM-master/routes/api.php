<?php

use Illuminate\Http\Request;
use App\Api\v1\Controllers\AuthController;

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

Route::prefix('v1')->group(function () {
    // Routes publiques
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/test', [AuthController::class, 'test']);

    // Routes protégées
    Route::middleware('auth:api')->group(function () {
        Route::get('/users', 'App\Api\v1\Controllers\UserController@index');
    });
});
