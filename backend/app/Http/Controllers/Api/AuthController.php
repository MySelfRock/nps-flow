<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a new tenant and user (signup)
     */
    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create tenant
            $tenant = Tenant::create([
                'name' => $request->tenant_name,
                'cnpj' => $request->cnpj,
                'plan' => 'free',
            ]);

            // Create user as super_admin
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'super_admin',
            ]);

            // Log action
            AuditLog::logAction('user_signup', $user, [
                'tenant_id' => $tenant->id,
                'email' => $user->email,
            ]);

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'tenant_id' => $user->tenant_id,
                    ],
                    'tenant' => [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'plan' => $tenant->plan,
                    ],
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ]
            ], 201);

        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('Signup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create account. Please try again later.',
            ], 500);
        }
    }

    /**
     * Login user and return JWT token
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = auth('api')->user();
        $user->updateLastLogin();

        // Log action
        AuditLog::logAction('user_login', $user, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'tenant_id' => $user->tenant_id,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ],
                'tenant' => [
                    'id' => $user->tenant->id,
                    'name' => $user->tenant->name,
                    'plan' => $user->tenant->plan,
                    'cnpj' => $user->tenant->cnpj,
                ],
            ]
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        $user = auth('api')->user();

        // Log action
        AuditLog::logAction('user_logout', $user);

        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'user_id' => auth('api')->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed. Please login again.',
            ], 401);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'tenant_id' => $user->tenant_id,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ]);
    }
}
