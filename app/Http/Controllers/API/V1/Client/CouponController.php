<?php

namespace App\Http\Controllers\API\V1\Client;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\API\V1\Client\ValidateCouponRequest;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{

    public function __construct(
                protected CouponService $couponService
        ){
        }



    public function couponValidate(ValidateCouponRequest $request){
        $data = $request->validated();
        try {
          $coupon = $this->couponService->validateCoupon($data['code']);
          return $this->successResponse([], __('coupon is valid'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

}
