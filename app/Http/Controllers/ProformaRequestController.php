<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProformaRequestRequest;
use App\Mail\ProformaRequestMail;
use App\Models\AppSetting;
use App\Models\CommercialAction;
use App\Models\PilotInvite;
use App\Models\ProformaRequest;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProformaRequestController extends Controller
{
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
