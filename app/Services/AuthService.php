<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    private const TOKEN_EXPIRES_IN_DAYS = 7;

    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        BusinessProfile::create([
            'user_id'       => $user->id,
            'business_name' => $data['business_name'],
            'category'      => $data['category'],
        ]);

        $token = $this->createAccessToken($user);

        return ['user' => $user->load('businessProfile'), 'token' => $token->plainTextToken];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $this->createAccessToken($user);

        return ['user' => $user->load('businessProfile'), 'token' => $token->plainTextToken];
    }

    private function createAccessToken(User $user): NewAccessToken
    {
        return $user->createToken(
            'auth_token',
            ['*'],
            now()->addDays(self::TOKEN_EXPIRES_IN_DAYS)
        );
    }
}
