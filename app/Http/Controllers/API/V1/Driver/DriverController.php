<?php

namespace App\Http\Controllers\API\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Driver\RatingResource;
use App\Services\DriverService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;



class DriverController extends Controller{

    public function __construct(protected DriverService $driverService){
    }
    public function earnings(){

        try{
            // Get date filter from request (0 = today, 1 = this week, 2 = this month)
            $dateFilter = request()->query('date');
            
            // Validate date filter if provided
            if ($dateFilter !== null && !in_array($dateFilter, ['0', '1', '2'])) {
                return $this->errorResponse(__('Invalid date filter. Use 0 for today, 1 for this week, or 2 for this month.'), 422);
            }
            
            $data = $this->driverService->earningStatistics($dateFilter);
            return $this->successResponse($data, __('Earning statistics retrieved successfully'));
        }catch(\Exception $e){
            Log::debug($e->getMessage(),$e->getTrace());
            return $this->handleException($e, __('Failed to retrieve earning statistics'));
        }

    }

    public function ratings(){
        $driver = Auth::user()->driver;
        if(!$driver){
            return $this->errorResponse(__('Driver not found'));
        }
    return $this->successResponse([
        'count'=>$driver->ratings->count(),
        'rate'=>$driver->ratings->avg('rating'),
        'ratings'=>RatingResource::collection($driver->ratings),
    ], __('Ratings retrieved successfully'));
    }

}