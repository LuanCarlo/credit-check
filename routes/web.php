<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/simulate-credit-offer', [App\Http\Controllers\CheckCreditOffersController::class, 'simulateCreditOfferPage']);
Route::get('/reports-credit-offer', [App\Http\Controllers\CheckCreditOffersController::class, 'reportCreditOfferPage']);


