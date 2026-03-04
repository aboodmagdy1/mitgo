<?php


use App\Http\Middleware\AppLocale;
use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'v1','middleware'=>[AppLocale::class,'api']], function () {


    
        require_once __DIR__ . '/API/V1/general.routes.php'; 
        require_once __DIR__ . '/API/V1/auth.routes.php'; 
    require_once __DIR__ . '/API/V1/client.routes.php'; 
    require_once __DIR__ . '/API/V1/driver.routes.php'; 
    });

