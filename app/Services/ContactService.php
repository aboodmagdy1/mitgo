<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Actions\Action;

class ContactService extends BaseService
{
    protected $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
        parent::__construct($contact);
    }

    /**
     * Get Contact with relationships
     */
    public function getWithRelations(array $relations = []): Collection
    {
        return $this->contact->with($relations)->get();
    }

    /**
     * Find Contact with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?Contact
    {
        return $this->contact->with($relations)->find($id);
    }

    /**
     * Create Contact with business logic
     */
    public function createWithBusinessLogic(array $data): Contact
    {
        $contact = $this->create($data);
        $this->afterCreate($contact);
        
        return $contact;
    }

    /**
     * Update Contact with business logic
     */
    public function updateWithBusinessLogic(Contact $contact, array $data): bool
    {
        $updated = $this->update($contact, $data);
        if ($updated) {
            $this->afterUpdate($contact);
        }
        
        return $updated;
    }

    /**
     * Delete Contact with business logic
     */
    public function deleteWithBusinessLogic(Contact $contact): bool
    {
        // Add your business logic here before deleting
        $this->validateDeletion($contact);
        
        $deleted = $this->delete($contact);
        
        if ($deleted) {
            // Add your business logic here after deleting
            $this->afterDelete($contact);
        }
        
        return $deleted;
    }



    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?Contact $contact = null): void
    {
        // Add your business validation logic here
        // Example: Check if required fields are present, validate relationships, etc.
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion(Contact $contact): void
    {
        // Add your deletion validation logic here
        // Example: Check if record can be deleted, has dependencies, etc.
    }

    /**
     * After create business logic
     */
    protected function afterCreate(Contact $contact): void
    {
       $this->sendAdminNotification(__('New contact message'), __('A new contact message has been received'), 
       [Action::make('view')
            ->url(route('filament.admin.resources.contacts.view', $contact->id))
            ->label(__('View'))
        ],'database');
    }

    /**
     * After update business logic
     */
    protected function afterUpdate(Contact $contact): void
    {
        // Add your post-update business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After delete business logic
     */
    protected function afterDelete(Contact $contact): void
    {
        // Add your post-deletion business logic here
        // Example: Clean up related records, send notifications, etc.
    }
}