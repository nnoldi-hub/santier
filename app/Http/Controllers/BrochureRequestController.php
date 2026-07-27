<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrochureRequestRequest;
use App\Mail\BrochureRequestMail;
use App\Models\AppSetting;
use App\Models\BrochureRequest;
use App\Support\MarketingPricing;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BrochureRequestController extends Controller
{
    public function store(StoreBrochureRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $brochureRequest = BrochureRequest::create([
            'tenant_id' => 1,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        $platformSettings = AppSetting::allWithDefaults(config('platform.defaults', []));

        $pdfBinary = Pdf::loadView('marketing.brochure', [
            'plans' => MarketingPricing::plans(),
            'settings' => $platformSettings,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4')->output();

        $fileName = 'brosura-modulia.pdf';
        $salesEmail = $platformSettings['sales_email'] ?? 'vanzari@modulia.ro';

        try {
            Mail::to($validated['email'])
                ->bcc($salesEmail)
                ->send(new BrochureRequestMail(
                    recipientName: $validated['name'],
                    pdfBinary: $pdfBinary,
                    fileName: $fileName,
                ));
        } catch (Throwable $e) {
            return back()->with('error', 'Trimiterea brosurii a esuat: ' . $e->getMessage());
        }

        $brochureRequest->update(['sent_at' => now()]);

        return back()->with('success', 'Brosura a fost trimisa la ' . $validated['email'] . '.');
    }
}
