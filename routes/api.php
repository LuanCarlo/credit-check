<?php

use App\Http\Controllers\CheckCreditOffersController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/getInstituitionsByCpf', [CheckCreditOffersController::class, 'getInstituitionsByCpf']);

Route::post('/getDetailByOffers', [CheckCreditOffersController::class, 'getDetailByOffers']);

Route::post('/getBestCreditOffersByCpf', [CheckCreditOffersController::class, 'getBestCreditOffersByCpf']);

Route::post('/calculateCreditConditions', [CheckCreditOffersController::class, 'calculateCreditConditions']);

