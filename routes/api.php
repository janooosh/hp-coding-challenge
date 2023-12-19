<?php

use App\Http\Controllers\ProductMergingController;
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

Route::middleware('auth:sanctum')->prefix('product-merger/{brand}')->group(function() {

    //  Get the required data before merging products (variants and indicate whether can be merged)
    Route::get('/',[ProductMergingController::class, 'index'])
    ->name('api.product-merging.get-product-data-for-merging');

    // Merge products together
    Route::post('/',[ProductMergingController::class, 'create'])
    ->name('api.product-merging.merge-product-data');
});
