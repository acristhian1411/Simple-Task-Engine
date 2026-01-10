<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends ApiController
{
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();
            if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
                return $this->errorResponse('Credenciales inválidas', 401);
            }
            $user = $request->user();
            $token = $user->createToken('api', ['expires_at' => now()->addMinutes(30)])->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if ($user && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
            return $this->showMessage('Sesión cerrada');
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function me(Request $request)
    {
        try {
            return $this->showOne($request->user());
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }

    public function register(Request $request){
        try {
            $user = User::create($request->all()); 
            return $this->showOne($user);  
        } catch (\Throwable $e) {
            return $this->respondException($e);
        }
    }
}
