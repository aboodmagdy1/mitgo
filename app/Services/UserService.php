<?php

namespace App\Services;

use App\Enums\Users\ProfileStatus;
use App\Enums\Users\UserTypeEnum;
use App\Http\Resources\API\V1\UserResource;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class UserService extends BaseService
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        parent::__construct($user);
    }

    /**
     * Get User with relationships
     */
    public function getWithRelations(array $relations = []): Collection
    {
        return $this->user->with($relations)->get();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->user->where('email', $email)->first();
    }
    public function findByPhone(string $phone): ?User
    {
        return $this->user->where('phone', $phone)->first();
    }

    /**
     * Find User with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?User
    {
        return $this->user->with($relations)->find($id);
    }

    public function verifyEmail(string $token): array
    {
        $tokenModel =PersonalAccessToken::findToken($token);
        if (!$tokenModel) {
            return [
                'verified' => false,
                'message' => __('Invalid verification token'),
            ];
        }

        if ($tokenModel->name !== 'email-verification') {
            return [
                'verified' => false,
                'message' => __('Invalid verification token'),
            ];
        }

        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            return [
                'verified' => false,
                'message' => __('Verification token has expired'),
            ];
        }

        $user = $tokenModel->tokenable;
        if (!$user) {
            return [
                'verified' => false,
                'message' => __('User not found')   ,
            ];
        }

        $user->markEmailAsVerified();
        $tokenModel->delete();

        return [
            'verified' => true,
            'message' => __('Email verified successfully'),
            'user' => $user,
        ];
    }
    public function create(array $data): User
    {
        info('create user', $data);
        $avatar = null;
        if(array_key_exists('avatar', $data)){
            $avatar = $data['avatar'];
            unset($data['avatar']);
        }
        $data['is_active'] = true;
        $exist = User::where('phone', $data['phone'])->first();
        //update if exist
        if($exist){
            $exist->update($data);
            // Handle avatar for existing user
            if($avatar){
                $exist->clearMediaCollection('avatar');
                $exist->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }
            return $exist;
        }
        $user = $this->user->create($data);
        if($avatar){
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
        return $user;
    }

    /**
     * Create User with business logic
     */
    public function createWithBusinessLogic(array $data): User
    {
        // Registration should only update the authenticated user's profile
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('User must be authenticated to register');
        }

        // Update user profile data
        $this->updateWithBusinessLogic($user, $data);
        
        // Ensure the user has the client role
        if (!$user->hasRole('client')) {
            $user->assignRole('client');
        }

        return $user;
    }

    public function generateOtp(): int
    {
        return env('APP_ENV') != 'production' ? 1234 : rand(1000,9999);
    }

    /**
     * Update User with business logic
     */
    public function updateWithBusinessLogic(User $user, array $data): bool
    {
        $avatar = null;
        if(array_key_exists('avatar', $data)){
            $avatar = $data['avatar'];
            unset($data['avatar']);
        }
        $updated = $this->update($user, $data);
        if($avatar){
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
        

        
        return $updated;
    }

    /**
     * Delete User with business logic
     */
    public function deleteWithBusinessLogic(User $user): bool
    {
        // Add your business logic here before deleting
        $this->validateDeletion($user);
        
        $deleted = $this->delete($user);
        
        if ($deleted) {
            // Add your business logic here after deleting
            $this->afterDelete($user);
        }
        
        return $deleted;
    }



    /**
     * Create user profile based on user type
     */


    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, ?User $user = null): void
    {
        // Add your business validation logic here
        // Example: Check if required fields are present, validate relationships, etc.
    }

    /**
     * Validate deletion
     */
    protected function validateDeletion(User $user): void
    {
        // Add your deletion validation logic here
        // Example: Check if record can be deleted, has dependencies, etc.
    }


    /**
     * After create business logic
     */
    protected function afterCreate(User $user, array $data): void
    {

       
    }



    /**
     * After update business logic
     */
    protected function afterUpdate(User $user): void
    {
        // Add your post-update business logic here
        // Example: Send notifications, update related records, etc.
    }

    /**
     * After delete business logic
     */
    protected function afterDelete(User $user): void
    {
        // Add your post-deletion business logic here
        // Example: Clean up related records, send notifications, etc.
    }

    /**
     * Generate and send reset password code
     */
    public function sendResetCode(string $email): array
    {
        $user = $this->findByEmail($email);
        if (!$user ) {
            return [
                'success' => false,
                'message' => __('User not found'),
            ];
        }
        if(!$user->hasVerifiedEmail()){
            return [
                'success' => false,
                'message' => __('Email not verified'),
            ];
        }
        // Generate 4-digit code
        $resetCode = $this->generateResetCode();
        
        // Save code with expiration (15 minutes)
        $user->update([
            'reset_code' => $resetCode,
            'reset_code_expires_at' => now()->addMinutes(15),
            'reset_code_verified' => false, // Reset verification status
        ]);

        // Send email with the code
        $user->sendPasswordResetNotification($resetCode);

        return [
            'success' => true,
            'message' => __('Reset code sent to your email'),
        ];
    }

    /**
     * Verify reset code
     */
    public function verifyResetCode(string $email, string $code): array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return [
                'success' => false,
                'message' => __('User not found'),
            ];
        }

        if (!$user->reset_code || $user->reset_code !== $code) {
            return [
                'success' => false,
                'message' => __('Invalid reset code'),
            ];
        }

        if ($user->reset_code_expires_at && $user->reset_code_expires_at->isPast()) {
            return [
                'success' => false,
                'message' => __('Reset code has expired'),
            ];
        }

        // Mark code as verified for this user
        $user->update([
            'reset_code_verified' => true,
        ]);

        return [
            'success' => true,
            'message' => __('Reset code verified successfully'),
        ];
    }

    /**
     * Update password after verification (no code needed)
     */
    public function updatePasswordAfterVerification(string $email, string $newPassword): array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return [
                'success' => false,
                'message' => __('User not found'),
            ];
        }

        // Check if reset code was verified and not expired
        if (!$user->reset_code_verified) {
            return [
                'success' => false,
                'message' => __('Reset code not verified. Please verify your reset code first.'),
            ];
        }

        if ($user->reset_code_expires_at && $user->reset_code_expires_at->isPast()) {
            return [
                'success' => false,
                'message' => __('Reset code has expired. Please request a new one.'),
            ];
        }

        // Update password and clear reset data
        $user->update([
            'password' => Hash::make($newPassword),
            'reset_code' => null,
            'reset_code_expires_at' => null,
            'reset_code_verified' => false,
        ]);

        return [
            'success' => true,
            'message' => __('Password updated successfully'),
        ];
    }

    /**
     * Generate 4-digit reset code
     */
    private function generateResetCode(): string
    {
        return str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function updateAccountSettings(User $user, array $data): array
    {
        if(array_key_exists('password', $data)){
            $data['password'] = Hash::make($data['password']);
        }
        if(array_key_exists('email_notifications', $data)){
            $user->notificationPreferences()->updateOrCreate(
                ['user_id' => $user->id],
                ['email_notifications' => $data['email_notifications']]
            );
        }
        if(array_key_exists('whatsapp_notifications', $data)){
            $user->notificationPreferences()->updateOrCreate(
                ['user_id' => $user->id],
                ['whatsapp_notifications' => $data['whatsapp_notifications']]
            );
        }
        $user->update($data);


       return [
        'success' => true,
        'message' => __('Account settings updated successfully'),
       ];
    }
}   