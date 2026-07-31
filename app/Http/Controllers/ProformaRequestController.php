<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProformaRequestRequest;
use App\Mail\ProformaRequestMail;
use App\Models\AppSetting;
use App\Models\CommercialAction;
use App\Models\PilotInvite;
use App\Models\ProformaRequest;
use App\Models\User;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProformaRequestController extends Controller
{
    public function adminIndex(Request $request): Response
    {
        $this->ensureAdmin($request);

        $requests = ProformaRequest::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProformaRequest $proformaRequest) {
                $invite = PilotInvite::query()
                    ->whereRaw('lower(contact_email) = ?', [strtolower($proformaRequest->contact_email)])
                    ->whereNotNull('converted_tenant_id')
                    ->latest('id')
                    ->first();

                return [
                    'id' => $proformaRequest->id,
                    'company_name' => $proformaRequest->company_name,
                    'company_cui' => $proformaRequest->company_cui,
                    'contact_name' => $proformaRequest->contact_name,
                    'contact_email' => $proformaRequest->contact_email,
                    'contact_phone' => $proformaRequest->contact_phone,
                    'plan_label' => config("pricing.plans.{$proformaRequest->plan}.label", $proformaRequest->plan),
                    'interval' => $proformaRequest->interval === 'yearly' ? 'Anual' : 'Lunar',
                    'discount_pct' => $proformaRequest->discount_pct,
                    'status' => $proformaRequest->status,
                    'created_at' => $proformaRequest->created_at->format('d.m.Y H:i'),
                    'converted_tenant_name' => $invite?->convertedTenant?->name,
                ];
            });

        return Inertia::render('Admin/ProformaRequests', [
            'requests' => $requests,
        ]);
    }

    public function markPaid(Request $request, ProformaRequest $proformaRequest): RedirectResponse
    {
        $this->ensureAdmin($request);

        $proformaRequest->update(['status' => 'paid']);

        return back()->with('success', 'Cererea a fost marcata ca platita. Daca firma are deja cont, activeaza planul din Admin > Firme & Abonamente.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($this->isAdmin($request->user()), 403);
    }

    private function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_superadmin ?? false)) {
            return true;
        }

        return in_array(strtolower($user->email), array_map('strtolower', config('platform.admin_emails', [])), true);
    }

    public function store(StoreProformaRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $proformaRequest = ProformaRequest::create([
            'tenant_id' => 1,
            ...$validated,
            'discount_pct' => 20,
            'ip_address' => $request->ip(),
        ]);

        $platformSettings = AppSetting::allWithDefaults(config('platform.defaults', []));
        $basePrice = (float) config("pricing.plans.{$validated['plan']}." . ($validated['interval'] === 'yearly' ? 'price_yearly' : 'price'), 0);
        $discountedPrice = round($basePrice * (1 - $proformaRequest->discount_pct / 100), 2);
        $generatedAt = now();
        $validUntil = $generatedAt->copy()->addDays(14);

        $pdfBinary = Pdf::loadView('billing.proforma', [
            'proformaRequest' => $proformaRequest,
            'issuer' => $platformSettings,
            'planLabel' => config("pricing.plans.{$validated['plan']}.label", $validated['plan']),
            'basePrice' => $basePrice,
            'discountedPrice' => $discountedPrice,
            'generatedAt' => $generatedAt,
            'validUntil' => $validUntil,
        ])->setPaper('a4')->output();

        $fileName = 'proforma-modulia-' . $proformaRequest->id . '.pdf';
        $salesEmail = $platformSettings['sales_email'] ?? 'vanzari@modulia.ro';

        try {
            Mail::to($validated['contact_email'])
                ->send(new ProformaRequestMail(
                    recipientName: $validated['contact_name'],
                    companyName: $validated['company_name'],
                    pdfBinary: $pdfBinary,
                    fileName: $fileName,
                ));
        } catch (Throwable $e) {
            return back()->with('error', 'Trimiterea facturii proforma a esuat: ' . $e->getMessage());
        }

        $proformaRequest->update(['sent_at' => now()]);

        try {
            Mail::to($salesEmail)
                ->send(new ProformaRequestMail(
                    recipientName: $validated['contact_name'],
                    companyName: $validated['company_name'],
                    pdfBinary: $pdfBinary,
                    fileName: $fileName,
                ));
        } catch (Throwable $e) {
            Log::warning('Notificarea de vanzari pentru cererea de proforma a esuat.', [
                'proforma_request_id' => $proformaRequest->id,
                'sales_email' => $salesEmail,
                'error' => $e->getMessage(),
            ]);
        }

        $this->trackPilotInvite($request, $proformaRequest);

        return back()->with('success', 'Factura proforma a fost trimisa la ' . $validated['contact_email'] . '.');
    }

    private function trackPilotInvite(Request $request, ProformaRequest $proformaRequest): void
    {
        $invite = PilotInvite::query()
            ->whereRaw('lower(contact_email) = ?', [strtolower($proformaRequest->contact_email)])
            ->latest('id')
            ->first();

        $note = 'Solicitare factura proforma cu discount ' . $proformaRequest->discount_pct . '% pentru planul ' . $proformaRequest->plan . ' (' . $proformaRequest->interval . ').';

        if ($invite) {
            $invite->update(['last_contacted_at' => now()]);
        } else {
            $invite = PilotInvite::create([
                'tenant_id' => TenantContext::id($request->user()),
                'owner_id' => null,
                'company_name' => $proformaRequest->company_name,
                'contact_name' => $proformaRequest->contact_name,
                'contact_email' => $proformaRequest->contact_email,
                'contact_phone' => $proformaRequest->contact_phone,
                'status' => 'contacted',
                'commercial_stage' => 'negotiation',
                'invited_at' => now(),
                'last_contacted_at' => now(),
            ]);
        }

        CommercialAction::create([
            'tenant_id' => $invite->tenant_id,
            'pilot_invite_id' => $invite->id,
            'actor_id' => $request->user()?->id,
            'action_type' => 'oferta',
            'notes' => $note,
        ]);
    }
}
