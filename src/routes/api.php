<?php

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

Route::post('refresh', [\My\Auth\Http\Controllers\AuthController::class, 'refresh']);
Route::post('logout', [\My\Auth\Http\Controllers\AuthController::class, 'logout']);
Route::post('register', [\My\Auth\Http\Controllers\AuthController::class, 'register']);
Route::post('login', [\My\Auth\Http\Controllers\AuthController::class, 'login']);