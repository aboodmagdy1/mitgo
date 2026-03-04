<?php

use App\Http\Controllers\API\V1\ContactController;
use App\Http\Controllers\API\V1\GeneralController;
use App\Http\Controllers\API\V1\TripController;
use Illuminate\Support\Facades\Route;

    //# General endpoints 
    Route::group(['as'=>'general.','prefix'=>'general'], function () {
        Route::get('/cities', [GeneralController::class, 'cities'])->name('cities');
        Route::get('/settings', [GeneralController::class, 'settings'])->name('settings');
        Route::get('/terms',[GeneralController::class,'terms']);
        Route::get('/about-us',[GeneralController::class,'aboutUs']);
        Route::get('/faqs', [GeneralController::class, 'faqs'])->name('faqs');
        Route::get('/payment-methods', [GeneralController::class, 'paymentMethods'])->name('payment-methods');
        Route::get('/trip-statuses', [GeneralController::class, 'tripStatuses'])->name('trip-statuses');
        Route::get('/rate-comments', [GeneralController::class, 'rateComments'])->name('rate-comments');
        Route::get('/cancel-reasons', [GeneralController::class, 'cancelReasons'])->name('cancel-reasons');
        Route::get('/vehicle-brands', [GeneralController::class, 'vehicleBrands'])->name('vehicle-brands');
        Route::get('/vehicle-brands/{vehicle_brand}/models', [GeneralController::class, 'vehicleBrandModels'])->name('vehicle-brands.models');
    });
    Route::group(['as'=>'contacts.','prefix'=>'contacts'], function () {
        Route::post('/', [ContactController::class, 'store'])->name('store');
    });


