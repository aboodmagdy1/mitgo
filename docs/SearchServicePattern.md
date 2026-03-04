# Search Service Pattern

This document explains the reusable search service pattern implemented in the application.

## Overview

The search functionality is implemented using a base abstract class `BaseSearchService` that provides common search logic, which can be extended by model-specific search services. This approach follows the DRY (Don't Repeat Yourself) principle and makes the code more maintainable.

## Architecture

### BaseSearchService (Abstract Class)

Located at: `app/Services/BaseSearchService.php`

This abstract class provides:
- **Generic search logic**: Exact match, partial match, fuzzy matching, SOUNDEX, and typo tolerance
- **Configurable search field**: Defaults to 'name' but can be customized
- **Default relations**: Pre-load relationships for better performance
- **Default filters**: Common filters that can be applied to any model
- **Error handling**: Graceful error handling with logging

### Model-Specific Search Services

Each model has its own search service that extends `BaseSearchService`:

#### AgencySearchService
- **File**: `app/Services/AgencySearchService.php`
- **Search field**: 'name'
- **Default relations**: ['media', 'services', 'industries', 'companySize', 'locations']
- **Specific filters**: Services, industries, company size, location/distance, rating

#### ProjectSearchService
- **File**: `app/Services/ProjectSearchService.php`
- **Search field**: 'name'
- **Default relations**: ['services', 'region', 'client', 'agency', 'currency']
- **Specific filters**: Budget range, work type, timeline, status, etc.

## Usage Examples

### Service Layer Integration

The search services are integrated into the main service classes:

```php
// AgencyService already uses AgencySearchService
class AgencyService extends BaseService
{
    public function __construct(Agency $agency, private AgencySearchService $searchService)
    {
        // ...
    }

    public function searchSuggestions($query, $limit = 10)
    {
        return $this->searchService->searchSuggestions($query, $limit);
    }

    public function search(SearchRequest $request)
    {
        return $this->searchService->searchWithFilters($request);
    }
}

// ProjectService uses ProjectSearchService
class ProjectService extends BaseService
{
    public function __construct(Project $project, private ProjectSearchService $searchService)
    {
        // ...
    }

    public function searchSuggestions($query, $limit = 10)
    {
        return $this->searchService->searchSuggestions($query, $limit);
    }

    public function search($request)
    {
        return $this->searchService->searchWithFilters($request);
    }
}
```

### Controller Usage

Controllers use the main service classes and follow the application's response pattern:

```php
class AgencyController extends Controller
{
    public function __construct(private AgencyService $service)
    {
    }

    public function searchSuggestions(Request $request)
    {
        try {
            $suggestions = $this->service->searchSuggestions($request->query('q'));
            return $this->collectionResponse(
                AgencyAgencySearchReslutResource::collection($suggestions), 
                __('Search suggestions retrieved successfully')
            );
        } catch (\Exception $e) {
            return $this->handleException($e, __('Failed to retrieve search suggestions'));
        }
    }

    public function search(SearchRequest $request)
    {
        try {
            $results = $this->service->search($request);
            return $this->paginatedResourceResponse(
                $results, 
                AgencyResource::class, 
                __('Search results retrieved successfully')
            );
        } catch (\Exception $e) {
            return $this->handleException($e, __('Failed to retrieve search results'));
        }
    }
}
```

### Creating a New Search Service

To create a search service for a new model (e.g., `Client`):

1. **Create the search service class**:

```php
<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

class ClientSearchService extends BaseSearchService
{
    public function __construct(Client $client)
    {
        parent::__construct(
            $client,
            'name', // search field
            ['city', 'companySize', 'user'], // default relations
            ['is_active'] // default filters
        );
    }

    /**
     * Apply client-specific filters
     */
    protected function applyFilters(Builder $queryBuilder, $request): Builder
    {
        // Apply base filters first
        $queryBuilder = parent::applyFilters($queryBuilder, $request);

        // Add client-specific filters here
        if ($request->has('city_id')) {
            $queryBuilder->where('city_id', $request->query('city_id'));
        }

        if ($request->has('company_size_id')) {
            $queryBuilder->where('company_size_id', $request->query('company_size_id'));
        }

        return $queryBuilder;
    }
}
```

2. **Update the main service class**:

```php
class ClientService extends BaseService
{
    public function __construct(Client $client, private ClientSearchService $searchService)
    {
        $this->client = $client;
        parent::__construct($client);
    }

    public function searchSuggestions($query, $limit = 10)
    {
        return $this->searchService->searchSuggestions($query, $limit);
    }

    public function search($request)
    {
        return $this->searchService->searchWithFilters($request);
    }
}
```

3. **Create the controller**:

```php
class ClientController extends Controller
{
    public function __construct(private ClientService $service)
    {
    }

    public function searchSuggestions(Request $request)
    {
        try {
            $suggestions = $this->service->searchSuggestions($request->query('q'));
            return $this->collectionResponse(
                ClientResource::collection($suggestions), 
                __('Search suggestions retrieved successfully')
            );
        } catch (\Exception $e) {
            return $this->handleException($e, __('Failed to retrieve search suggestions'));
        }
    }
}
```

4. **Add routes**:

```php
// routes/API/V1/client.routes.php
Route::group(['as' => 'clients.search.', 'prefix' => 'clients'], function () {
    Route::get('/search-suggestions', [ClientController::class, 'searchSuggestions'])->name('search-suggestions');
    Route::get('/search', [ClientController::class, 'search'])->name('search');
});
```

## API Response Pattern

The application uses a standardized response pattern through the `ApiResponseTrait`:

### Collection Responses
```php
return $this->collectionResponse(
    Resource::collection($data), 
    __('Message')
);
```

### Paginated Resource Responses
```php
return $this->paginatedResourceResponse(
    $paginatedData, 
    ResourceClass::class, 
    __('Message')
);
```

### Error Handling
```php
try {
    // Your logic here
} catch (\Exception $e) {
    return $this->handleException($e, __('Error message'));
}
```

## API Endpoints

The application provides RESTful endpoints for search functionality:

### Agency Search
- `GET /api/v1/agencies/search-suggestions` - Get agency search suggestions
- `GET /api/v1/agencies/search` - Search agencies with filters

### Project Search
- `GET /api/v1/projects/search-suggestions` - Get project search suggestions
- `GET /api/v1/projects/search` - Search projects with filters
- `GET /api/v1/projects/recent` - Get recent projects
- `GET /api/v1/projects/featured` - Get featured projects
- `GET /api/v1/projects/status/{status}` - Get projects by status

## Query Parameters

### Common Parameters
- `q` - Search query
- `per_page` - Number of results per page (default: 9)
- `limit` - Limit for suggestions (default: 10)

### Agency-Specific Filters
- `services[]` - Filter by service IDs
- `industries[]` - Filter by industry IDs
- `company_size[]` - Filter by company size IDs
- `lat`, `lng`, `radius` - Location-based filtering
- `min_rating` - Minimum rating filter
- `all_services` - Filter agencies that have any services

### Project-Specific Filters
- `services[]` - Filter by service IDs
- `budget_min`, `budget_max` - Budget range filtering
- `budget_type[]` - Budget type filtering
- `work_type[]` - Work type filtering
- `location_type[]` - Location type filtering
- `region_id[]` - Region filtering
- `timeline_start`, `timeline_end` - Timeline filtering
- `client_id[]` - Client filtering
- `agency_id[]` - Agency filtering
- `currency_id[]` - Currency filtering
- `date_from`, `date_to` - Date range filtering
- `status[]` - Status filtering
- `type[]` - Project type filtering

## Benefits

1. **Code Reusability**: Common search logic is shared across all models
2. **Maintainability**: Changes to search logic only need to be made in one place
3. **Consistency**: All search services follow the same pattern and behavior
4. **Extensibility**: Easy to add new search services for new models
5. **Performance**: Optimized queries with proper indexing and eager loading
6. **Error Handling**: Robust error handling with logging
7. **Standardized Responses**: Consistent API response format across all endpoints

## Best Practices

1. **Always extend BaseSearchService** for new search services
2. **Override applyFilters()** for model-specific filtering logic
3. **Use appropriate default relations** to avoid N+1 queries
4. **Add model-specific methods** for specialized search functionality
5. **Follow the service layer pattern** - inject search service into main service
6. **Use standardized response methods** from ApiResponseTrait
7. **Create proper request validation** for search endpoints
8. **Test thoroughly** with various search scenarios
9. **Monitor performance** and optimize queries as needed

## Performance Considerations

- The base service includes relevance scoring for better result ordering
- Default relations are eager-loaded to prevent N+1 queries
- Fuzzy matching is optimized for common use cases
- Error handling prevents crashes and provides meaningful feedback
- Pagination is built-in to handle large result sets
- Search services are properly integrated into the service layer for better separation of concerns 