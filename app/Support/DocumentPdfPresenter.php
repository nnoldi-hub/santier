<?php

namespace App\Support;

use App\Models\Document;

class DocumentPdfPresenter
{
    /**
     * Derives every computed value the "Proces verbal" PDF layouts need from
     * the document and its branding - shared by all layout variants.
     */
    public static function present(Document $document, array $branding): array
    {
        $issuedAt = $document->issued_at ? $document->issued_at->format('d.m.Y') : now()->format('d.m.Y');
        $executionPeriod = '-';

        if ($document->stage?->start_date && $document->stage?->end_date) {
            $executionPeriod = $document->stage->start_date->format('d.m.Y') . ' - ' . $document->stage->end_date->format('d.m.Y');
        } elseif ($document->project?->start_date && $document->project?->end_date) {
            $executionPeriod = $document->project->start_date->format('d.m.Y') . ' - ' . $document->project->end_date->format('d.m.Y');
        }

        $internalCodePrefix = $document->type === 'proc_verbal_receptie' ? 'PVR' : ($document->type === 'proc_verbal_constatare' ? 'PVC' : 'DOC');
        $internalCode = $internalCodePrefix . '-' . ($document->issued_at ? $document->issued_at->format('Ym') : now()->format('Ym')) . '-' . str_pad((string) $document->id, 5, '0', STR_PAD_LEFT);
        $uniqueCode = 'UID-' . strtoupper(substr(sha1((string) $document->id . '|' . (string) $document->created_at . '|' . $document->title), 0, 12));
        $isConform = $document->payment_status !== 'cancelled';
        $documentIssuer = trim((string) ($branding['document_issuer_name'] ?? ''));
        $whiteLabel = $branding['white_label'] ?? false;
        $fallbackLogo = public_path('brand/logo_modulia.png');
        $logoSource = !empty($branding['document_logo_url'])
            ? $branding['document_logo_url']
            : (!$whiteLabel && file_exists($fallbackLogo) ? $fallbackLogo : null);
        $acceptanceText = $isConform
            ? 'Lucrarea a fost receptionata si acceptata conform verificarilor efectuate.'
            : 'Lucrarea necesita remedieri inainte de receptia finala.';

        return [
            'issuedAt' => $issuedAt,
            'executionPeriod' => $executionPeriod,
            'internalCode' => $internalCode,
            'uniqueCode' => $uniqueCode,
            'isConform' => $isConform,
            'documentIssuer' => $documentIssuer,
            'whiteLabel' => $whiteLabel,
            'logoSource' => $logoSource,
            'acceptanceText' => $acceptanceText,
        ];
    }
}
