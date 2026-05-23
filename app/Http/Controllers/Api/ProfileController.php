<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($request->user()->load('businessProfile')),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
            $user->save();
        }

        $profileData = [
            'business_name'         => $validated['business_name'] ?? $user->businessProfile?->business_name,
            'category'              => $validated['business_category'] ?? $user->businessProfile?->category,
            'description'           => $validated['business_description'] ?? $user->businessProfile?->description,
            'dark_mode'             => $validated['dark_mode'] ?? $user->businessProfile?->dark_mode,
            'language'              => $validated['language'] ?? $user->businessProfile?->language,
            'notification_enabled'  => $validated['notification_enabled'] ?? $user->businessProfile?->notification_enabled,
            'ai_tone'               => $validated['ai_tone'] ?? $user->businessProfile?->ai_tone,
            'default_target_market' => $validated['default_target_market'] ?? $user->businessProfile?->default_target_market,
            'default_platform'      => $validated['default_platform'] ?? $user->businessProfile?->default_platform,
        ];

        if ($request->hasFile('profile_photo')) {
            $profileData['profile_photo'] = Storage::url(
                $request->file('profile_photo')->store('profile_photos', 'public')
            );
        }

        $user->businessProfile()->updateOrCreate(
            ['user_id' => $user->id],
            array_filter($profileData, fn($value) => $value !== null)
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => new UserResource($user->load('businessProfile')),
        ]);
    }
}
