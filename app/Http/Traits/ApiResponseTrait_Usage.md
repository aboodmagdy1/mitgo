# API Response Trait Usage Guide

This trait provides standardized API responses across your Laravel application. All methods return consistent JSON responses with proper HTTP status codes.

## Basic Response Structure

All responses follow this structure:
```json
{
    "success": true|false,
    "message": "Description of the operation",
    "data": {}, // Only present for successful responses
    "errors": {}, // Only present for error responses
    "pagination": {}, // Only present for paginated responses
    "meta": {} // Only present when using responseWithMeta()
}
```

## Success Responses (2xx)

### Standard Success Response
```php
// 200 OK
public function index()
{
    $users = User::all();
    return $this->ok($users, 'Users retrieved successfully');
}
```

### Resource Created
```php
// 201 Created
public function store(Request $request)
{
    $user = User::create($request->validated());
    return $this->created($user, 'User created successfully');
}
```

### Accepted for Processing
```php
// 202 Accepted
public function processLargeFile(Request $request)
{
    // Queue the job
    ProcessFileJob::dispatch($request->file('upload'));
    return $this->accepted(null, 'File queued for processing');
}
```

### No Content
```php
// 204 No Content
public function destroy(User $user)
{
    $user->delete();
    return $this->noContent();
}
```

## Resource Responses

### Single Resource
```php
public function show(User $user)
{
    return $this->resourceResponse(new UserResource($user), 'User retrieved successfully');
}
```

### Resource Collection
```php
public function index()
{
    $users = User::all();
    return $this->collectionResponse(UserResource::collection($users), 'Users retrieved successfully');
}
```

### Paginated Response
```php
public function index()
{
    $users = User::paginate(15);
    return $this->paginatedResponse($users, 'Users retrieved successfully');
}
```

## Error Responses (4xx & 5xx)

### Bad Request
```php
// 400 Bad Request
if (!$request->has('required_field')) {
    return $this->badRequest('Required field is missing');
}
```

### Unauthorized
```php
// 401 Unauthorized
if (!auth()->check()) {
    return $this->unauthorized('Please login to access this resource');
}
```

### Forbidden
```php
// 403 Forbidden
if (!$user->can('edit', $post)) {
    return $this->forbidden('You do not have permission to edit this post');
}
```

### Not Found
```php
// 404 Not Found
$user = User::find($id);
if (!$user) {
    return $this->notFound('User not found');
}
```

### Validation Error
```php
// 422 Unprocessable Entity
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users',
        'name' => 'required|string|max:255'
    ]);
    
    if ($validator->fails()) {
        return $this->handleValidationErrors($validator);
        // or manually:
        // return $this->validationError('Validation failed', $validator->errors());
    }
}
```

### Conflict
```php
// 409 Conflict
if (User::where('email', $request->email)->exists()) {
    return $this->conflict('User with this email already exists');
}
```

### Too Many Requests
```php
// 429 Rate Limited
if ($this->rateLimitExceeded()) {
    return $this->tooManyRequests('Rate limit exceeded. Please try again later.');
}
```

### Server Errors
```php
// 500 Internal Server Error
try {
    // Some operation
} catch (\Exception $e) {
    return $this->handleException($e, 'Failed to process request');
    // or manually:
    // return $this->internalServerError('Something went wrong');
}
```

## Convenience Methods

### Exception Handling
```php
public function update(Request $request, User $user)
{
    try {
        $user->update($request->validated());
        return $this->ok($user, 'User updated successfully');
    } catch (\Exception $e) {
        return $this->handleException($e, 'Failed to update user');
    }
}
```

### Empty Data Response
```php
public function search(Request $request)
{
    $results = $this->searchService->search($request->query);
    
    if ($results->isEmpty()) {
        return $this->emptyDataResponse('No results found for your search');
    }
    
    return $this->ok($results, 'Search completed successfully');
}
```

### Boolean Response
```php
public function checkAvailability(Request $request)
{
    $isAvailable = $this->service->checkUsernameAvailability($request->username);
    
    return $this->booleanResponse(
        $isAvailable,
        'Username is available',
        'Username is already taken'
    );
}
```

### Response with Meta Data
```php
public function analytics()
{
    $data = $this->analyticsService->getData();
    $meta = [
        'generated_at' => now()->toISOString(),
        'version' => '1.0',
        'cache_expires' => now()->addHours(1)->toISOString()
    ];
    
    return $this->responseWithMeta($data, $meta, 'Analytics data retrieved');
}
```

### Custom Response
```php
public function customEndpoint()
{
    return $this->customResponse(
        ['custom' => 'data'],
        'Custom operation completed',
        202, // status code
        true // success flag (optional, auto-determined from status code)
    );
}
```

## Complete Controller Example

```php
<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::paginate(15);
            return $this->paginatedResponse($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve users');
        }
    }

    public function show(User $user)
    {
        try {
            return $this->resourceResponse(new UserResource($user), 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve user');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8'
            ]);

            if ($validator->fails()) {
                return $this->handleValidationErrors($validator);
            }

            $user = User::create($request->validated());
            return $this->created(new UserResource($user), 'User created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to create user');
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id
            ]);

            if ($validator->fails()) {
                return $this->handleValidationErrors($validator);
            }

            $user->update($request->validated());
            return $this->ok(new UserResource($user), 'User updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to update user');
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return $this->noContent();
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to delete user');
        }
    }
}
```

## Best Practices

1. **Always use try-catch blocks** for database operations and external API calls
2. **Provide meaningful messages** that help frontend developers understand what happened
3. **Use appropriate HTTP status codes** for different scenarios
4. **Handle validation errors consistently** using the trait's validation methods
5. **Use resource responses** for structured data output
6. **Include pagination** for list endpoints that might return large datasets
7. **Log errors** while providing user-friendly error messages in responses

## Response Examples

### Successful Response
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        }
    ]
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "name": ["The name field is required."]
    }
}
```

### Paginated Response
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "from": 1,
        "to": 15,
        "has_more_pages": true
    }
}
``` 

# UserService Profile Creation Usage

The `UserService` has been enhanced to automatically create agency or brand/client profiles when users are registered. Here's how to use it:

## Basic Usage (Automatic Profile Creation)

```php
use App\Services\UserService;
use App\Enums\Users\UserTypeEnum;

// Inject the service (Laravel will handle dependencies automatically)
public function __construct(private UserService $userService) {}

// Create a user - profile will be created automatically
public function registerUser(array $userData, UserTypeEnum $type)
{
    $user = $this->userService->createWithBusinessLogic($userData, $type);
    // Agency or Client profile is now automatically created!
    return $user;
}
```

## Advanced Usage (With Custom Profile Data)

```php
// Create agency with custom profile data
public function registerAgency(array $userData, array $agencyProfileData = [])
{
    $profileData = [
        'name' => 'Custom Agency Name',
        'tagline' => 'We create amazing experiences',
        'brief' => 'Custom agency description...',
        'city_id' => 1,
        'company_size_id' => 2,
        // ... any other agency-specific data
    ];
    
    $user = $this->userService->createWithBusinessLogic(
        $userData, 
        UserTypeEnum::Agency, 
        $profileData
    );
    
    return $user;
}

// Create brand/client with custom profile data
public function registerBrand(array $userData, array $clientProfileData = [])
{
    $profileData = [
        'name' => 'Custom Brand Name',
        'city_id' => 1,
        'company_size_id' => 2,
        'project_price_range_id' => 3,
        'vat_number' => '123456789',
        // ... any other client-specific data
    ];
    
    $user = $this->userService->createWithBusinessLogic(
        $userData, 
        UserTypeEnum::Brand, 
        $profileData
    );
    
    return $user;
}
```

## What Happens Automatically

1. **User Creation**: The user record is created with the provided data
2. **Profile Creation**: Based on the user type:
   - `UserTypeEnum::Agency` → Creates an Agency profile
   - `UserTypeEnum::Brand` or `UserTypeEnum::Individual` → Creates a Client profile
   - `UserTypeEnum::User` → No profile created (admin users)
3. **Email Verification**: Sends email verification notification
4. **Admin Notification**: Notifies super admins of new registration

## Default Profile Data

### Agency Profiles Get:
- `user_id`: Linked to the user
- `name`: User's full name or "New Agency"
- `email`: User's email
- `phone`: User's phone
- `tagline`: "Professional agency services"
- `brief`: Default description
- `profile_completed`: false

### Client Profiles Get:
- `user_id`: Linked to the user  
- `name`: User's full name or "New Client"

Any additional data passed in `$profileData` will override these defaults.

## Benefits of This Approach

1. **Clean Separation**: Business logic stays in services
2. **Automatic**: No need to manually create profiles after user creation
3. **Flexible**: Can provide custom profile data when needed
4. **Consistent**: All user types follow the same pattern
5. **Maintainable**: Central location for profile creation logic 