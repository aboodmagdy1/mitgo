<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\ContactRequest;
use App\Services\ContactService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private ContactService $contactService)
    {
        
    }
    public function store(ContactRequest $request)
    {
       try{
        $this->contactService->createWithBusinessLogic($request->validated());
        return $this->successResponse(null, __('Contact message sent successfully'));
       }catch(\Exception $e){
        return $this->errorResponse(__('Failed to send contact message'), 500);
       }
    }
}
