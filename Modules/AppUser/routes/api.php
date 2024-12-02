<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\AppUser\App\Http\Controllers\AppUserController;
use Modules\AppUser\App\Http\Controllers\AuthController;

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

Route::group([
    'prefix' => 'app-user/auth'
], function ($router) {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/check_number', [AppUserController::class, 'check_number']);
    Route::post('/check_opt', [AppUserController::class, 'check_opt']);
});
