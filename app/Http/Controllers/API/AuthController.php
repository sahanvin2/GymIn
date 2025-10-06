<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
			]);

			$token = $user->createToken('auth_token')->plainTextToken;

			return response()->json([
				'success' => true,
				'message' => 'User registered successfully',
				'data' => [
					'user' => [
						'id' => $user->id,
						'name' => $user->name,
						'email' => $user->email,
						'is_admin' => $user->is_admin ?? false,
						'created_at' => $user->created_at,
					],
					'token' => $token,
					'token_type' => 'Bearer'
				]
			], 201);

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $e->errors()
			], 422);
		}
	}

	/**
	 * Login user
	 */
	public function login(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
	{
		try {
			$request->validate([
				'email' => 'required|email',
				'password' => 'required|string',
			]);

			if (!\Illuminate\Support\Facades\Auth::attempt($request->only('email', 'password'))) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid credentials'
				], 401);
			}

			$user = \Illuminate\Support\Facades\Auth::user();
			$token = $user->createToken('auth_token')->plainTextToken;

			return response()->json([
				'success' => true,
				'message' => 'Login successful',
				'data' => [
					'user' => [
						'id' => $user->id,
						'name' => $user->name,
						'email' => $user->email,
						'is_admin' => $user->is_admin ?? false,
						'created_at' => $user->created_at,
					],
					'token' => $token,
					'token_type' => 'Bearer'
				]
			]);

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $e->errors()
			], 422);
		}
	}

	/**
	 * Get authenticated user
	 */
	public function user(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
	{
		$user = $request->user();

		return response()->json([
			'success' => true,
			'message' => 'User retrieved successfully',
			'data' => [
				'user' => [
					'id' => $user->id,
					'name' => $user->name,
					'email' => $user->email,
					'is_admin' => $user->is_admin ?? false,
					'created_at' => $user->created_at,
					'updated_at' => $user->updated_at,
				]
			]
		]);
	}

	/**
	 * Logout user
	 */
	public function logout(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
	{
		$request->user()->currentAccessToken()->delete();

		return response()->json([
			'success' => true,
			'message' => 'Logout successful'
		]);
	}

	/**
	 * Refresh user token
	 */
	public function refresh(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
	{
		$user = $request->user();
		$request->user()->currentAccessToken()->delete();
		$token = $user->createToken('auth_token')->plainTextToken;

		return response()->json([
			'success' => true,
			'message' => 'Token refreshed successfully',
			'data' => [
				'token' => $token,
				'token_type' => 'Bearer'
			]
		]);
	}

	/**
	 * Update user profile
	 */
	public function updateProfile(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
	{
		try {
			$user = $request->user();
			$request->validate([
				'name' => 'sometimes|required|string|max:255',
				'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
				'current_password' => 'required_with:password|string',
				'password' => 'sometimes|string|min:8|confirmed',
			]);

			if ($request->has('password')) {
				if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
					return response()->json([
						'success' => false,
						'message' => 'Current password is incorrect'
					], 400);
				}
			}

			$updateData = [];
			if ($request->has('name')) $updateData['name'] = $request->name;
			if ($request->has('email')) $updateData['email'] = $request->email;
			if ($request->has('password')) $updateData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);

			$user->update($updateData);

			return response()->json([
				'success' => true,
				'message' => 'Profile updated successfully',
				'data' => [
					'user' => [
						'id' => $user->id,
						'name' => $user->name,
						'email' => $user->email,
						'is_admin' => $user->is_admin ?? false,
						'updated_at' => $user->updated_at,
					]
				]
			]);

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'errors' => $e->errors()
			], 422);
		}
	}
}
