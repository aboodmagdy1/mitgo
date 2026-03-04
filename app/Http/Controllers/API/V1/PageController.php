<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\PageResource;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(private PageService $service)
    {
        
    }
    public function index(){
        $pages = $this->service->getWithRelations();
        if($pages->isEmpty()){
            return $this->notFound(__('Pages not found'));
        }
        return $this->collectionResponse(PageResource::collection($pages), __('Pages retrieved successfully'));
    }
    public function show($slug){
        $page = $this->service->findBySlug($slug);
        if (!$page) {
            return $this->notFound(__('Page not found'));
        }

        return $this->successResponse(PageResource::make($page), __('Page retrieved successfully'));
    }
}