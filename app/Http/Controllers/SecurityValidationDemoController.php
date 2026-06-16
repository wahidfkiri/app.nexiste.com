<?php

namespace App\Http\Controllers;

use App\Http\Requests\Security\StoreValidationDemoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SecurityValidationDemoController extends Controller
{
    public function create(Request $request)
    {
        return view('security.validation-demo', [
            'saved' => session('validation_demo_saved', null),
        ]);
    }

    public function store(StoreValidationDemoRequest $request)
    {
        $validated = $request->validated();

        $payload = [
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'age' => $validated['age'] ?? null,
            'budget' => $validated['budget'] ?? null,
            'phone' => $validated['phone'],
            'website' => $validated['website'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'role' => $validated['role'],
            'contact_channel' => $validated['contact_channel'],
            'interests' => $validated['interests'] ?? [],
            'avatar_uploaded' => $request->hasFile('avatar'),
            'attachment_uploaded' => $request->hasFile('attachment'),
            'saved_at' => now()->toDateTimeString(),
        ];

        session()->flash('validation_demo_saved', $payload);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Validation sécurisée OK. Données traitées avec succès.',
                'data' => $payload,
            ]);
        }

        return redirect()->route('security.validation-demo')->with('success', 'Validation sécurisée OK.');
    }
}
