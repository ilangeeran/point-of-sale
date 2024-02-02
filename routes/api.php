<?php

use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EcommerceController;

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

Route::post('user/login', [UserController::class, 'login']);
Route::post('user/register', [UserController::class, 'store']);
Route::post('user/forgot-password', [UserController::class, 'forgotPasswordApi']);
Route::post('user/password/verify', [UserController::class, 'verifyPasswordApi']);
Route::post('user/password/reset', [UserController::class, 'resetPasswordApi']);

Route::group(["middleware" => 'auth:api'], function () {
    Route::get('user', [UserController::class, 'getProfileApi']);
    Route::post('user', [UserController::class, 'updateProfileApi']);

    Route::get('brands', [EcommerceController::class, 'brands']);

    Route::get('banners', [EcommerceController::class, 'banners']);
    
    Route::get('categories', [EcommerceController::class, 'categories']);
    
    Route::get('products', [EcommerceController::class, 'products']);

    Route::get('wishlists', [EcommerceController::class, 'wishlists']);
    Route::post('wishlists', [EcommerceController::class, 'toggleWishlists']);
});