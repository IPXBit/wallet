<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Wallet\App\Http\Controllers\AlertController;
use Modules\Wallet\App\Http\Controllers\WalletController;

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
    'middleware' => ['tokencheck'],

], function ($router) {
  ///wallet
  Route::get('/user-balance', [WalletController::class, 'userBalance']);
  Route::post('/add-balance', [WalletController::class, 'addBalance']);
  Route::post('/transfer-balance', [WalletController::class, 'transferBalance']);
  Route::get('/donation', [WalletController::class, 'GetDonatePartner']);
  Route::post('donation/{id}', [WalletController::class, 'donation']);
  Route::post('alert', [AlertController::class, 'store']);
});
