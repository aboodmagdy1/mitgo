# Service Classes Usage Guide

This guide shows how to use the generated Service classes for business logic in your Laravel application.

## Overview

The service classes provide a clean way to handle business logic separated from your controllers and models. Each service extends the `BaseService` class and provides specific methods for handling business operations.

## Command Usage

### Generate Services for All Models
```bash
php artisan generate:services
```

### Generate Service for Specific Model
```bash
php artisan generate:services --model=User
php artisan generate:services --model=Agency
```

## Service Structure

Each generated service includes:

1. **BaseService Methods**: Common CRUD operations
2. **Business Logic Methods**: Methods with validation and business rules
3. **Relationship Methods**: Methods for handling model relationships
4. **Validation Methods**: Custom validation logic
5. **Lifecycle Methods**: Before/after operation hooks

## Usage Examples

### Basic Usage in Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\AgencyService;
use App\Services\UserService;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    protected $agencyService;
    protected $userService;

    public function __construct(AgencyService $agencyService, UserService $userService)
    {
        $this->agencyService = $agencyService;
        $this->userService = $userService;
    }

    public function index()
    {
        // Get all agencies with relationships
        $agencies = $this->agencyService->getWithRelations(['user', 'services', 'languages']);
        
        return view('agencies.index', compact('agencies'));
    }

    public function store(Request $request)
    {
        // Create agency with business logic validation
        $agency = $this->agencyService->createWithBusinessLogic($request->validated());
        
        return redirect()->route('agencies.show', $agency->id);
    }

    public function show($id)
    {
        // Find agency with relationships
        $agency = $this->agencyService->findWithRelations($id, ['user', 'services', 'teamMembers']);
        
        return view('agencies.show', compact('agency'));
    }

    public function update(Request $request, $id)
    {
        $agency = $this->agencyService->findByIdOrFail($id);
        
        // Update with business logic
        $this->agencyService->updateWithBusinessLogic($agency, $request->validated());
        
        return redirect()->route('agencies.show', $agency->id);
    }
}
```

### Service Injection in Other Services

```php
<?php

namespace App\Services;

class ProjectService extends BaseService
{
    protected $agencyService;
    
    public function __construct(Project $project, AgencyService $agencyService)
    {
        parent::__construct($project);
        $this->agencyService = $agencyService;
    }
    
    public function createProjectForAgency(int $agencyId, array $projectData): Project
    {
        $agency = $this->agencyService->findByIdOrFail($agencyId);
        
        // Add business logic here
        $this->validateProjectData($projectData, $agency);
        
        $project = $this->create(array_merge($projectData, ['agency_id' => $agencyId]));
        
        // Post-creation business logic
        $this->notifyAgencyOfNewProject($agency, $project);
        
        return $project;
    }
}
```

### Working with Relationships

```php
// Get agency's services
$services = $this->agencyService->getServices($agency);

// Add a new service to agency
$service = $this->agencyService->addService($agency, [
    'name' => 'Web Development',
    'description' => 'Full-stack web development services'
]);

// Sync services (for belongsToMany relationships)
$this->agencyService->syncServices($agency, [1, 2, 3]);

// Attach services
$this->agencyService->attachServices($agency, [4, 5]);

// Detach services
$this->agencyService->detachServices($agency, [1, 2]);
```

### Custom Business Logic

Each service includes hooks for custom business logic:

```php
<?php

namespace App\Services;

class AgencyService extends BaseService
{
    protected function validateBusinessRules(array $data, ?Agency $agency = null): void
    {
        // Custom validation logic
        if (isset($data['email']) && $this->emailExists($data['email'], $agency)) {
            throw new \Exception('Email already exists');
        }
        
        // Validate agency requirements
        if (isset($data['services']) && count($data['services']) < 1) {
            throw new \Exception('Agency must have at least one service');
        }
    }
    
    protected function afterCreate(Agency $agency): void
    {
        // Send welcome email
        Mail::to($agency->email)->send(new WelcomeAgencyMail($agency));
        
        // Create default settings
        $this->createDefaultSettings($agency);
        
        // Log activity
        activity()->log('Agency created: ' . $agency->name);
    }
    
    protected function afterUpdate(Agency $agency): void
    {
        // Clear cache
        Cache::forget("agency.{$agency->id}");
        
        // Update search index
        $agency->searchable();
        
        // Notify subscribers
        event(new AgencyUpdated($agency));
    }
    
    protected function validateDeletion(Agency $agency): void
    {
        // Check if agency has active projects
        if ($agency->projects()->where('status', 'active')->exists()) {
            throw new \Exception('Cannot delete agency with active projects');
        }
        
        // Check if agency has pending subscriptions
        if ($agency->subscriptionRequests()->where('status', 'pending')->exists()) {
            throw new \Exception('Cannot delete agency with pending subscriptions');
        }
    }
}
```

## Available Methods

### BaseService Methods (Available in All Services)

- `getAll()` - Get all records
- `getPaginated($perPage = 15)` - Get paginated records
- `findById($id)` - Find record by ID
- `findByIdOrFail($id)` - Find record by ID or throw exception
- `create($data)` - Create new record
- `update($model, $data)` - Update existing record
- `delete($model)` - Delete record
- `getWhere($conditions)` - Get records with conditions
- `count()` - Count all records
- `exists($id)` - Check if record exists

### Generated Service Methods

- `getWithRelations($relations = [])` - Get all records with relationships
- `findWithRelations($id, $relations = [])` - Find record with relationships
- `createWithBusinessLogic($data)` - Create with business validation
- `updateWithBusinessLogic($model, $data)` - Update with business validation
- `deleteWithBusinessLogic($model)` - Delete with business validation

### Relationship Methods (Generated based on model relationships)

For `hasMany` relationships:
- `get{RelationName}($model)` - Get related records
- `add{SingularRelationName}($model, $data)` - Add new related record

For `belongsToMany` relationships:
- `sync{RelationName}($model, $ids)` - Sync related records
- `attach{RelationName}($model, $ids)` - Attach related records
- `detach{RelationName}($model, $ids)` - Detach related records

## Best Practices

1. **Dependency Injection**: Always inject services in constructors
2. **Business Logic**: Keep all business logic in services, not controllers
3. **Validation**: Use the provided validation hooks for business rules
4. **Transactions**: Wrap complex operations in database transactions
5. **Error Handling**: Use custom exceptions for business logic errors
6. **Testing**: Write unit tests for your service methods

## Example Test

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Services\AgencyService;
use Tests\TestCase;

class AgencyServiceTest extends TestCase
{
    protected $agencyService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->agencyService = app(AgencyService::class);
    }
    
    public function test_can_create_agency_with_business_logic()
    {
        $data = [
            'name' => 'Test Agency',
            'email' => 'test@example.com',
            'user_id' => 1
        ];
        
        $agency = $this->agencyService->createWithBusinessLogic($data);
        
        $this->assertInstanceOf(Agency::class, $agency);
        $this->assertEquals('Test Agency', $agency->name);
    }
}
```

This service architecture provides a clean, maintainable, and testable way to handle business logic in your Laravel application. 