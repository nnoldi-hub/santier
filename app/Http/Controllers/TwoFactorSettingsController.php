<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        $user = $request->user();

        if (! $enabled) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user->trustedDevices()->delete();
        }

        $user->update(['two_factor_enabled' => $enabled]);

        return back()->with('success', $enabled
            ? 'Autentificarea in doi factori a fost activata.'
            : 'Autentificarea in doi factori a fost dezactivata.');
    }
}
