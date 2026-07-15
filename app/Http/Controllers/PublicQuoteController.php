<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Support\DocumentBranding;
use App\Support\QuoteBreakdownResolver;
use App\Support\QuotePdfPresenter;
use Inertia\Inertia;
use Inertia\Response;

class PublicQuoteController extends Controller
{
    /**
     * Signed, unauthenticated view of a quote - reached via the "Vezi oferta
     * online" link sent to the client's email. Shows exactly the same data
     * already sent in the PDF attachment, just rendered as a web page.
     */
    public function show(Quote $quote): Response
    {
        $quote->loadMissing(['project:id,name', 'creator:id,name', 'items']);
        $branding = DocumentBranding::resolve((int) $quote->tenant_id);

        [$displayNotes, $breakdown] = QuoteBreakdownResolver::extractBreakdownFromNotes((string) ($quote->notes ?? ''));

        if (!is_array($breakdown) && $quote->items->isNotEmpty()) {
            $breakdown = QuoteBreakdownResolver::buildBreakdownFromItems($quote->items);
        }

        $meta = is_array($quote->meta) ? $quote->meta : [];
        $presented = QuotePdfPresenter::present($quote, $meta, is_array($breakdown) ? $breakdown : [], $displayNotes, $branding);

        return Inertia::render('Public/QuoteShow', array_merge($presented, [
            'quote' => $quote,
            'branding' => $branding,
        ]));
    }
}
