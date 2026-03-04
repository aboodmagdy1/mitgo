<?php

namespace App\Services;

use App\Models\DriverWithdrawRequest;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action;

class WalletService extends BaseService 
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }
    /**
     * Get user's wallet history with filtering
     */
    public function getWalletHistory(User $user, int $filter = 2)
    {
        // Build query for transactions
        $query = $user->transactions()->orderBy('created_at', 'desc');
        
        // Apply date filtering
        switch($filter) {
            case 1: // Day
                $query->where('created_at', '>=', Carbon::today());
                break;
            case 2: // Week (default)
                $query->where('created_at', '>=', Carbon::now()->startOfWeek());
                break;
            case 3: // Month
                $query->where('created_at', '>=', Carbon::now()->startOfMonth());
                break;
        }
        
        $transactions = $query->get();
        
        return [
            'balance' => $user->balance / 100,
            'transactions' => $transactions,
        ];
    }
    
    /**
     * Handle wallet deposit
     */
    public function deposit(User $user, float $amount)
    {
        $user->deposit($amount * 100, [
            'number' => random_int(100000, 999999),
        ]);
        
        return true;
    }
    
    /**
     * Handle wallet withdrawal request
     */
    public function withdraw(User $user): DriverWithdrawRequest
    {
        // Validate user authorization
        if (!$user->hasRole('driver')) {
            throw new \InvalidArgumentException(__('You are not authorized to withdraw from wallet'));
        }
        
       
        // Check if user has sufficient balance
        if ($user->balance / 100 <= 0) {
            throw new \InvalidArgumentException(__('Insufficient balance for withdrawal'));
        }
        
        // Check for existing pending withdrawal request
        $existingRequest = $user->driver->withdrawRequests()->where('is_approved', false)->first();
        if ($existingRequest) {
            throw new \LogicException(__('You have a pending withdrawal request'));
        }
        
        // Create new withdrawal request
        $withdrawalRequest = $user->driver->withdrawRequests()->create([
            'amount' => $user->balance / 100,
            'is_approved' => false,
        ]);

        $this->afterCreate($withdrawalRequest);
        
        return $withdrawalRequest;
    }

    protected function afterCreate(DriverWithdrawRequest $withdrawalRequest): void
    {
        $this->sendAdminNotification(__('New withdrawal request'), __('A new withdrawal request has been received'), 
        [Action::make('view')
            ->url(route('filament.admin.resources.driver-withdraw-requests.view', $withdrawalRequest->id))
            ->label(__('View'))
        ],'database');
    }
}
