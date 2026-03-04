<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Auth\LoginRequest;
use App\Http\Resources\API\V1\UserResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Http\Requests\API\V1\Auth\ActiveCodeRequest;

class BaseAuthCrontroller extends Controller
{   
    use ApiResponseTrait;
    public function __construct(protected UserService $userService, protected AuthService $authService)
    {
        
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);
        $result = $this->userService->verifyEmail($request->token);
        if(!$result['verified']){
            return $this->errorResponse($result['message'],400);
        }

        $user = $result['user'];
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => UserResource::make($user),
        ],$result['message'],200);
    }

    public function login(LoginRequest $request){
      return  $this->authService->login($request->validated());
    }

    public function verifyActiveCode(ActiveCodeRequest $request){
        return $this->authService->verifyActiveCode($request->validated());
    }

    public function resendVerificationEmail(Request $request){
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email',$request->input('email'))->first();
        if(!$user){
            return $this->errorResponse(__('User not found'),404);
        }
        
        $user->sendEmailVerificationNotification();
        return $this->successResponse([],__('Verification email sent successfully'),200);
    }

    public function logout(Request $request){
        return $this->authService->logout($request);
    }

    public function sendResetCode(Request $request){
        $request->validate([
            'email' => 'required|email',
        ]);
        
        $result = $this->userService->sendResetCode($request->input('email'));
        
        if(!$result['success']){
            return $this->errorResponse($result['message'], 404);
        }
        
        return $this->successResponse([], $result['message'], 200);
    }

    public function verifyResetCode(Request $request){
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:4',
        ]);
        
        $result = $this->userService->verifyResetCode(
            $request->input('email'),
            $request->input('code')
        );
        
        if(!$result['success']){
            return $this->errorResponse($result['message'], 400);
        }
        
        return $this->successResponse([], $result['message'], 200);
    }

    public function updatePassword(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $result = $this->userService->updatePasswordAfterVerification(
            $request->input('email'),
            $request->input('password')
        );
        
        if(!$result['success']){
            return $this->errorResponse($result['message'], 400);
        }
        
        return $this->successResponse([], $result['message'], 200);
    }
}