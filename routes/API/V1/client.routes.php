<?php

use App\Http\Controllers\API\V1\Client\HomeController;
use App\Http\Controllers\API\V1\Client\SavedLocationController;
use App\Http\Controllers\API\V1\Client\TripController;
use App\Http\Controllers\API\V1\Client\CouponController;
use Illuminate\Support\Facades\Route;

    //# General endpoints 
    Route::group(['as'=>'client.','prefix'=>'client'], function () {

        // # Coupon endpoints
        Route::post('/coupon-validate', [CouponController::class, 'couponValidate'])->name('coupon-validate');

        Route::get('/home', [HomeController::class, 'home'])->middleware('auth:sanctum')->name('home');

        // # Trips endpoints
        Route::group(['as'=>'trips.','prefix'=>'trips'], function () {
            Route::post('/vehicle-types-list', [TripController::class, 'vehicleTypesList'])->name('vehicle-types-list');
            Route::group(['middleware' => ['auth:sanctum']], function () {
                Route::post('/', [TripController::class, 'createTrip'])->name('create');
                Route::get('/', [TripController::class, 'index'])->name('index');
                Route::get('/{trip}', [TripController::class, 'show'])->name('show');
                Route::post('/{trip}/cancel', [TripController::class, 'cancelTrip'])->name('cancel');
                // Route::post('/{trip}/search-next-wave', [TripController::class, 'searchNextWave'])->name('search-next-wave'); // Deprecated: use restart-search
                Route::post('/{trip}/restart-search', [TripController::class, 'restartSearch'])->name('restart-search');
                Route::post('/{trip}/rate', [TripController::class, 'rateTrip'])->name('rate');
                Route::post('/{trip}/confirm-online-payment', [TripController::class, 'confirmOnlinePayment'])->name('confirm-online-payment');
    
            });
        });

        // # Saved locations endpoints
        Route::group(['as'=>'saved-locations.','prefix'=>'saved-locations','middleware' => ['auth:sanctum']], function () {
            Route::get('/', [SavedLocationController::class, 'savedLocations'])->name('index');
            Route::post('/', [SavedLocationController::class, 'store'])->name('store');
            Route::post('/{savedLocation}', [SavedLocationController::class, 'update'])->name('update');
            Route::delete('/{savedLocation}', [SavedLocationController::class, 'destroy'])->name('destroy');
        });
    });


