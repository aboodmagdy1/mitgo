<?php

namespace App\Http\Controllers\API\V1\Client;

use App\Http\Controllers\Controller;
use App\Services\HomeService;

class HomeController extends Controller
{

    public function __construct(
        protected HomeService $homeService,
    ) {
    }


    public function home (){
        $data = $this->homeService->getHomeData();
        return $this->successResponse($data, __('Home data retrieved successfully'));
    }
}
