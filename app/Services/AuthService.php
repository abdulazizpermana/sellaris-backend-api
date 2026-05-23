<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user->load('businessProfile'), 'token' => $token];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user->load('businessProfile'), 'token' => $token];
    }
}
