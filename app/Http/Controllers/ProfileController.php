<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Support\Security\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(protected PhoneNumberService $phoneNumbers)
    {
    }

    public function show(Request $request)
    {
        return view('profile-settings', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $data = $request->validated();

        if (!empty($data['new_password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], (string) $user->password)) {
                return back()->withErrors([
                    'current_password' => 'Le mot de passe actuel est incorrect.',
                ])->withInput();
            }

            $user->password = Hash::make($data['new_password']);
        }

        if ($request->hasFile('avatar')) {
            if (!empty($user->avatar) && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $normalizedPhone = $phone !== ''
            ? ($this->phoneNumbers->normalizeInternational($phone) ?? $phone)
            : null;

        $user->fill([
            'first_name' => $firstName ?: null,
            'last_name' => $lastName ?: null,
            'name' => $data['name'],
            'email' => $data['email'],
            'company' => $data['company'] ?? null,
            'phone' => $normalizedPhone,
            'position' => $data['position'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);

        $user->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                ],
            ]);
        }

        return redirect()->route('profile-settings')->with('success', 'Profil mis à jour avec succès.');
    }
}
