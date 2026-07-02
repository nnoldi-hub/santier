<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentBrandingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Documents/Branding', [
            'settings' => AppSetting::allWithDefaults(config('platform.defaults', [])),
            'colorPresets' => [
                ['name' => 'Portocaliu Profesional', 'value' => '#f97316'],
                ['name' => 'Albastru Corporate', 'value' => '#1d4ed8'],
                ['name' => 'Verde Clasic', 'value' => '#059669'],
                ['name' => 'Grafit Elegant', 'value' => '#334155'],
                ['name' => 'Rosu Premium', 'value' => '#b91c1c'],
                ['name' => 'Turcoaz Modern', 'value' => '#0f766e'],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'document_issuer_name' => ['nullable', 'string', 'max:120'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'sales_email' => ['required', 'email', 'max:255'],
            'document_logo_url' => ['nullable', 'url', 'max:500'],
            'document_logo_file' => ['nullable', 'image', 'max:2048'],
            'document_brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $documentLogoUrl = $validated['document_logo_url'] ?? '';

        if ($request->hasFile('document_logo_file')) {
            $path = $request->file('document_logo_file')->store('branding', 'public');
            $documentLogoUrl = Storage::url($path);
        }

        AppSetting::setValues([
            'company_name' => $validated['company_name'],
            'document_issuer_name' => $validated['document_issuer_name'] ?? '',
            'company_phone' => $validated['company_phone'] ?? '',
            'company_address' => $validated['company_address'] ?? '',
            'support_email' => $validated['support_email'],
            'sales_email' => $validated['sales_email'],
            'document_logo_url' => $documentLogoUrl,
            'document_brand_color' => $validated['document_brand_color'],
        ]);

        return back()->with('success', 'Configurarea documentelor a fost actualizata.');
    }
}
