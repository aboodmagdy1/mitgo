<?php

use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\ContactController;
use App\Http\Controllers\API\V1\GeneralController;
use App\Http\Controllers\API\V1\WalletController;
use Illuminate\Support\Facades\Route;

    //# General endpoints 
    Route::group(['as'=>'auth.', 'prefix'=>'auth'], function () {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('verify-code', [AuthController::class, 'verifyActiveCode'])->name('verify-active-code');
        Route::post('send-otp', [AuthController::class, 'resendActiveCode'])->name('resend-active-code')
        ->middleware('throttle:1,1');

    Route::group(['middleware' => 'auth:sanctum', 'as'=>'auth.'], function () {
        Route::post('client/register', [AuthController::class, 'clientRegister'])->name('client.register');
        Route::post('driver/register', [AuthController::class, 'driverRegister'])->name('driver.register');

            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('change-lang', [AuthController::class, 'changeLanguage'])->name('change-language');
            Route::post('delete-account', [AuthController::class, 'deleteAccount'])->name('delete-account');
            Route::post('update-location', [AuthController::class, 'updateLocation'])->name('update-location');
            Route::group(['prefix' => 'client', 'as' => 'client.', 'middleware' => 'role:client'], function () {
                Route::get('profile', [AuthController::class, 'getClientProfile'])->name('profile');
                Route::post('profile', [AuthController::class, 'updateClientProfile'])->name('update-profile');
            });

            Route::group(['prefix' => 'driver', 'as' => 'driver.', 'middleware' => 'role:driver'], function () {
                Route::get('profile', [AuthController::class, 'getDriverProfile'])->name('profile');
                Route::post('status', [AuthController::class, 'toggleDriverStatus'])->name('status');
                Route::post('profile', [AuthController::class, 'updateDriverProfile'])->name('update-profile');
            });
    
        });

   
    });
    
    Route::group(['middleware' => 'auth:sanctum','as'=>'wallet.','prefix'=>'wallet'], function () {
        Route::get('history', [WalletController::class, 'history'])->name('history');
        Route::post('deposit', [WalletController::class, 'deposit'])->name('deposit');
        Route::post('withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    });