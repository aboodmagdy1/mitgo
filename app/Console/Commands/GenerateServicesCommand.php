<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:services {--model= : Generate service for specific model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Service classes for Models with business logic structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = $this->option('model');
        
        if ($modelName) {
            $this->generateServiceForModel($modelName);
        } else {
            $this->generateServicesForAllModels();
        }
    }

    /**
     * Generate services for all models
     */
    protected function generateServicesForAllModels()
    {
        $this->info('Generating services for all models...');
        
        // Create Services directory if it doesn't exist
        $servicesPath = app_path('Services');
        if (!File::exists($servicesPath)) {
            File::makeDirectory($servicesPath, 0755, true);
        }

        // Create BaseService first
        $this->createBaseService();

        // Get all model files
        $modelPath = app_path('Models');
        $modelFiles = File::files($modelPath);

        foreach ($modelFiles as $file) {
            $modelName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $this->generateServiceForModel($modelName);
        }

        $this->info('Services generated successfully!');
    }

    /**
     * Generate service for a specific model
     */
    protected function generateServiceForModel(string $modelName)
    {
        $modelPath = app_path("Models/{$modelName}.php");
        
        if (!File::exists($modelPath)) {
            $this->error("Model {$modelName} does not exist!");
            return;
        }

        $serviceName = "{$modelName}Service";
        $servicePath = app_path("Services/{$serviceName}.php");

        if (File::exists($servicePath)) {
            if (!$this->confirm("Service {$serviceName} already exists. Overwrite?")) {
                return;
            }
        }

        // Create Services directory if it doesn't exist
        $servicesPath = app_path('Services');
        if (!File::exists($servicesPath)) {
            File::makeDirectory($servicesPath, 0755, true);
        }

        // Create BaseService if it doesn't exist
        if (!File::exists(app_path('Services/BaseService.php'))) {
            $this->createBaseService();
        }

        $serviceContent = $this->generateServiceContent($modelName);
        
        File::put($servicePath, $serviceContent);
        
        $this->info("Service {$serviceName} generated successfully!");
    }

    /**
     * Create the base service class
     */
    protected function createBaseService()
    {
        $baseServicePath = app_path('Services/BaseService.php');
        
        if (File::exists($baseServicePath)) {
            return;
        }

        $baseServiceContent = $this->generateBaseServiceContent();
        File::put($baseServicePath, $baseServiceContent);
        
        $this->info("BaseService created successfully!");
    }

    /**
     * Generate base service content
     */
    protected function generateBaseServiceContent(): string
    {
        return <<<'PHP'
<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated records
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Find a record by ID
     */
    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a record by ID or fail
     */
    public function findByIdOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Delete a record
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Get records with conditions
     */
    public function getWhere(array $conditions): Collection
    {
        $query = $this->model->newQuery();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->get();
    }

    /**
     * Count records
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }
}
PHP;
    }

    /**
     * Generate service content for a specific model
     */
    protected function generateServiceContent(string $modelName): string
    {
        $serviceName = "{$modelName}Service";
        $modelNamespace = "App\\Models\\{$modelName}";
        
        // Read the model file to extract relationships
        $modelPath = app_path("Models/{$modelName}.php");
        $modelContent = File::get($modelPath);
        
        $relationships = $this->extractRelationships($modelContent);
        $businessMethods = $this->generateBusinessMethods($modelName, $relationships);

        return <<<PHP
<?php

namespace App\Services;

use App\Models\\{$modelName};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class {$serviceName} extends BaseService
{
    protected \${$this->getModelVariable($modelName)};

    public function __construct({$modelName} \${$this->getModelVariable($modelName)})
    {
        \$this->{$this->getModelVariable($modelName)} = \${$this->getModelVariable($modelName)};
        parent::__construct(\${$this->getModelVariable($modelName)});
    }

    /**
     * Get {$modelName} with relationships
     */
    public function getWithRelations(array \$relations = []): Collection
    {
        return \$this->{$this->getModelVariable($modelName)}->with(\$relations)->get();
    }

    /**
     * Find {$modelName} with relationships
     */
    public function findWithRelations(int \$id, array \$relations = []): ?{$modelName}
    {
        return \$this->{$this->getModelVariable($modelName)}->with(\$relations)->find(\$id);
    }

    /**
     * Create {$modelName} with business logic
     */
    public function createWithBusinessLogic(array \$data): {$modelName}
    {
        // Add your business logic here before creating
        \$this->validateBusinessRules(\$data);
        
        \${$this->getModelVariable($modelName)} = \$this->create(\$data);
        
        // Add your business logic here after creating
        \$this->afterCreate(\${$this->getModelVariable($modelName)});
        
        return \${$this->getModelVariable($modelName)};
    }

    /**
     * Update {$modelName} with business logic
     */
    public function updateWithBusinessLogic({$modelName} \${$this->getModelVariable($modelName)}, array \$data): bool
    {
        // Add your business logic here before updating
        \$this->validateBusinessRules(\$data, \${$this->getModelVariable($modelName)});
        
        \$updated = \$this->update(\${$this->getModelVariable($modelName)}, \$data);
        
        if (\$updated) {
            // Add your business logic here after updating
            \$this->afterUpdate(\${$this->getModelVariable($modelName)});
        }
        
        return \$updated;
    }

    /**
     * Delete {$modelName} with business logic
     */
    public function deleteWithBusinessLogic({$modelName} \${$this->getModelVariable($modelName)}): bool
    {
        // Add your business logic here before deleting
        \$this->validateDeletion(\${$this->getModelVariable($modelName)});
        
        \$deleted = \$this->delete(\${$this->getModelVariable($modelName)});
        
        if (\$deleted) {
            // Add your business logic here after deleting
            \$this->afterDelete(\${$this->getModelVariable($modelName)});
        }
        
        return \$deleted;
    }

{$businessMethods}

    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array \$data, ?{$modelName} \${$this->getModelVariable($modelName)} = null): void
    {
        // Add your business validation logic here
        // Example: Check if required fields are present, validate relationships, etc.
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion({$modelName} \${$this->getModelVariable($modelName)}): void
    {
        // Add your deletion validation logic here
        // Example: Check if record can be deleted, has dependencies, etc.
    }

    /**
     * After create business logic
     */
    protected function afterCreate({$modelName} \${$this->getModelVariable($modelName)}): void
    {
        // Add your post-creation business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After update business logic
     */
    protected function afterUpdate({$modelName} \${$this->getModelVariable($modelName)}): void
    {
        // Add your post-update business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After delete business logic
     */
    protected function afterDelete({$modelName} \${$this->getModelVariable($modelName)}): void
    {
        // Add your post-deletion business logic here
        // Example: Clean up related records, send notifications, etc.
    }
}
PHP;
    }

    /**
     * Extract relationships from model content
     */
    protected function extractRelationships(string $content): array
    {
        $relationships = [];
        
        // Extract hasMany, hasOne, belongsTo, belongsToMany relationships
        preg_match_all('/public function (\w+)\(\)\s*\{[^}]*return \$this->(hasMany|hasOne|belongsTo|belongsToMany)/s', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $relationName) {
                $relationships[] = [
                    'name' => $relationName,
                    'type' => $matches[2][$index]
                ];
            }
        }
        
        return $relationships;
    }

    /**
     * Generate business methods based on relationships
     */
    protected function generateBusinessMethods(string $modelName, array $relationships): string
    {
        $methods = [];
        
        foreach ($relationships as $relationship) {
            $relationName = $relationship['name'];
            $relationType = $relationship['type'];
            
            if ($relationType === 'hasMany') {
                $methods[] = $this->generateHasManyMethods($modelName, $relationName);
            } elseif ($relationType === 'belongsToMany') {
                $methods[] = $this->generateBelongsToManyMethods($modelName, $relationName);
            }
        }
        
        return implode("\n\n", $methods);
    }

    /**
     * Generate hasMany relationship methods
     */
    protected function generateHasManyMethods(string $modelName, string $relationName): string
    {
        $modelVariable = $this->getModelVariable($modelName);
        
        return <<<PHP
    /**
     * Get {$relationName} for {$modelName}
     */
    public function get{$this->studlyCase($relationName)}({$modelName} \${$modelVariable}): Collection
    {
        return \${$modelVariable}->{$relationName};
    }

    /**
     * Add {$relationName} to {$modelName}
     */
    public function add{$this->singularStudlyCase($relationName)}({$modelName} \${$modelVariable}, array \$data): Model
    {
        return \${$modelVariable}->{$relationName}()->create(\$data);
    }
PHP;
    }

    /**
     * Generate belongsToMany relationship methods
     */
    protected function generateBelongsToManyMethods(string $modelName, string $relationName): string
    {
        $modelVariable = $this->getModelVariable($modelName);
        
        return <<<PHP
    /**
     * Sync {$relationName} for {$modelName}
     */
    public function sync{$this->studlyCase($relationName)}({$modelName} \${$modelVariable}, array \$ids): array
    {
        return \${$modelVariable}->{$relationName}()->sync(\$ids);
    }

    /**
     * Attach {$relationName} to {$modelName}
     */
    public function attach{$this->studlyCase($relationName)}({$modelName} \${$modelVariable}, array \$ids): void
    {
        \${$modelVariable}->{$relationName}()->attach(\$ids);
    }

    /**
     * Detach {$relationName} from {$modelName}
     */
    public function detach{$this->studlyCase($relationName)}({$modelName} \${$modelVariable}, array \$ids = []): int
    {
        return \${$modelVariable}->{$relationName}()->detach(\$ids);
    }
PHP;
    }

    /**
     * Get model variable name
     */
    protected function getModelVariable(string $modelName): string
    {
        return Str::camel($modelName);
    }

    /**
     * Convert to studly case
     */
    protected function studlyCase(string $value): string
    {
        return Str::studly($value);
    }

    /**
     * Convert to singular studly case
     */
    protected function singularStudlyCase(string $value): string
    {
        return Str::studly(Str::singular($value));
    }
} 