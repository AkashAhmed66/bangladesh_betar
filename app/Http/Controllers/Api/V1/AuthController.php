<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * M01 — public listener authentication (FR-USR-02).
 * Token-based (Sanctum personal access tokens) for the public apps.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'locale' => ['nullable', Rule::in(['en', 'bn'])],
            'accept_terms' => ['accepted'], // FR-USR-12
        ]);

        $user = User::query()->create([
            'user_type' => 'listener',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'locale' => $data['locale'] ?? 'bn',
            'status' => 'active',
            'tos_accepted_at' => now(),
            'tos_version' => '1.0',
        ]);
        $user->assignRole('Listener');

        AuditLog::record('listener_registered', $user, null, null, 'Public registration');

        return $this->tokenResponse($user, 'Registration successful.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $data['email'])->where('user_type', 'listener')->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if ($user->status === 'banned') {
            throw ValidationException::withMessages(['email' => ['This account has been suspended.']]);
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $this->tokenResponse($user, 'Login successful.', 200, $data['device_name'] ?? 'public-app');
    }

    /**
     * FR-USR-02 — phone/OTP entry point. OTP delivery is stubbed; in
     * production this dispatches an SMS and verifies the code.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate(['phone' => ['required', 'string', 'max:30']]);

        return response()->json([
            'message' => 'OTP sent (demo: use 123456).',
            'expires_in' => 300,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'otp' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['otp'] !== '123456') {
            throw ValidationException::withMessages(['otp' => ['Invalid or expired OTP.']]);
        }

        $user = User::query()->firstOrCreate(
            ['phone' => $data['phone'], 'user_type' => 'listener'],
            [
                'name' => $data['name'] ?? 'Listener '.substr($data['phone'], -4),
                'email' => 'phone_'.preg_replace('/\D/', '', $data['phone']).'@listener.betar.local',
                'password' => Hash::make(str()->random(24)),
                'status' => 'active',
                'locale' => 'bn',
                'tos_accepted_at' => now(),
                'tos_version' => '1.0',
            ],
        );
        $user->syncRoles(['Listener']);

        return $this->tokenResponse($user, 'Phone verified.', 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $entitlements = app(\App\Services\EntitlementService::class)->entitlements($user);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'locale' => $user->locale,
                'avatar_url' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
                'preferences' => $user->preferences,
                'entitlements' => $entitlements,
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'locale' => ['sometimes', Rule::in(['en', 'bn'])],
            'preferences' => ['sometimes', 'array'],
            'preferences.personalization_opt_out' => ['sometimes', 'boolean'],
            'preferences.notifications' => ['sometimes', 'array'],
        ]);

        $user->update($data);

        return response()->json(['message' => 'Profile updated.', 'data' => $data]);
    }

    private function tokenResponse(User $user, string $message, int $status, string $device = 'public-app'): JsonResponse
    {
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'message' => $message,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'is_premium' => $user->isPremium(),
            ],
        ], $status);
    }
}
