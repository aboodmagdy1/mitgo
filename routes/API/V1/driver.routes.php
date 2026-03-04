<?php

use App\Http\Controllers\API\V1\Driver\DriverController;
use App\Http\Controllers\API\V1\Driver\TripController;
use Illuminate\Support\Facades\Route;

    //# General endpoints 
    Route::group(['as'=>'driver.','prefix'=>'driver'], function () {


        Route::group(['middleware' => ['auth:sanctum','role:driver']], function () {
            Route::get('/earnings', [DriverController::class, 'earnings'])->name('earnings');
            Route::get('/ratings', [DriverController::class, 'ratings'])->name('ratings');
            Route::get('/home', [TripController::class, 'home'])->name('home');
        });
        // # Trips endpoints
         Route::group(['as'=>'trips.','prefix'=>'trips'], function () {
            Route::group(['middleware' => ['auth:sanctum','role:driver']], function () {
                Route::get('/', [TripController::class, 'index'])->name('index');
                Route::get('/{trip}', [TripController::class, 'show'])->name('show');
                Route::post('/{trip}/accept', [TripController::class, 'accept'])->name('accept');
                Route::post('/{trip}/reject', [TripController::class, 'rejectTrip'])->name('reject');
                Route::post('/{trip}/status', [TripController::class, 'updateStatus'])->name('update-status');
                // Route::post('/{trip}/arrived', [TripController::class, 'arrived'])->name('arrived');
                // Route::post('/{trip}/cancel', [TripController::class, 'cancelTrip'])->name('cancel');
                // Route::post('/{trip}/no-show', [TripController::class, 'markRiderNoShow'])->name('no-show');
                // Route::post('/{trip}/start', [TripController::class, 'start'])->name('start');
                // Route::post('/{trip}/end', [TripController::class, 'end'])->name('end');
                Route::post('/{trip}/confirm-cash-payment', [TripController::class, 'confirmCashPayment'])->name('confirm-cash-payment');
    
            });

           
        });

    });


