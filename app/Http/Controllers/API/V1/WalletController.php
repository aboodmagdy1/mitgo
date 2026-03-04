<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\TransactionResrouce;
use App\Http\Traits\ApiResponseTrait;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller {
    use ApiResponseTrait;

    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function history(Request $request){
        $user = Auth::user();
        
        try {
            // Get filter parameter (1=day, 2=week, 3=month), default is 2 (week)
            $filter = $request->get('filter', 2);
            
            $walletData = $this->walletService->getWalletHistory($user, $filter);
            
            return $this->successResponse([
                'balance' => $walletData['balance'],
                'transactions' => TransactionResrouce::collection($walletData['transactions']),
            ], __('History retrieved successfully'));
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve wallet history', [
                'user_id' => $user->id,
                'filter' => $request->get('filter', 2),
                'error' => $e->getMessage()
            ]);
            return $this->internalServerError(__('Failed to retrieve wallet history'));
        }
    }

    public function deposit(Request $request){
        $user = Auth::user();
        
        try {
            $this->walletService->deposit($user, $request->amount);
            
            return $this->ok([
                'new_balance' => $user->fresh()->balance / 100
            ], __('Deposit successful'));
            
        } catch (\Exception $e) {
            Log::error('Deposit failed', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'error' => $e->getMessage()
            ]);
            return $this->internalServerError(__('Failed to process deposit'));
        }
    }

    public function withdraw(Request $request){
        $user = Auth::user();
        
        try {
            $withdrawalRequest = $this->walletService->withdraw($user);
            
            return $this->created([
                'withdrawal_request_id' => $withdrawalRequest->id,
                'amount' => $withdrawalRequest->amount,
                'status' => 'pending'
            ], __('Withdrawal request sent to admin for approval'));
            
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        } catch (\LogicException $e) {
            return $this->conflict($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Withdrawal request failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->internalServerError(__('Failed to process withdrawal request'));
        }
    }

}