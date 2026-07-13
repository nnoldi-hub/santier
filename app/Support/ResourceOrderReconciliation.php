<?php

namespace App\Support;

use App\Models\ResourceConfirmation;
use App\Models\ResourceOrder;
use Illuminate\Support\Collection;

class ResourceOrderReconciliation
{
    /**
     * Compares ordered vs delivered vs pump vs consumed quantities for a single
     * resource order, using the MAX of each re-reported value (each document/delivery
     * is treated as a re-report of the same physical quantity, not an additive volume).
     *
     * @param Collection<int, array<string, mixed>> $linkedDocuments
     * @param Collection<int, array<string, mixed>> $deliveries
     * @return array<string, mixed>
     */
    public static function summarize(ResourceOrder $order, Collection $linkedDocuments, Collection $deliveries): array
    {
        $tolerance = (float) config('resources.quantity_tolerance', 0.20);
        $ordered = (float) $order->ordered_quantity;

        $deliveredValues = $linkedDocuments
            ->whereIn('role', ['delivery_note', 'carrier_note'])
            ->pluck('delivered_quantity')
            ->filter(fn ($value) => $value !== null);

        $pumpFromDocs = $linkedDocuments
            ->where('role', 'pump_note')
            ->pluck('delivered_quantity')
            ->filter(fn ($value) => $value !== null);

        $pumpFromDeliveries = $deliveries
            ->pluck('equipment_reported_quantity')
            ->filter(fn ($value) => $value !== null);

        $consumedValues = $deliveries
            ->pluck('consumed_quantity')
            ->filter(fn ($value) => $value !== null);

        $hasDelivered = $deliveredValues->isNotEmpty();
        $hasPump = $pumpFromDocs->isNotEmpty() || $pumpFromDeliveries->isNotEmpty();
        $hasConsumed = $consumedValues->isNotEmpty();
        $hasLinkedDocumentDifference = $linkedDocuments->isNotEmpty();

        $delivered = $hasDelivered ? (float) $deliveredValues->max() : null;
        $pump = $hasPump
            ? (float) max((float) ($pumpFromDocs->max() ?? 0), (float) ($pumpFromDeliveries->max() ?? 0))
            : null;
        $consumed = $hasConsumed ? (float) $consumedValues->max() : null;

        $checks = [
            [
                'key' => 'declared_vs_delivered_documents',
                'label' => 'Declarat vs livrat (documente)',
                'left' => null,
                'right' => null,
                'is_applicable' => $hasLinkedDocumentDifference,
                'delta' => $hasLinkedDocumentDifference
                    ? (float) $linkedDocuments->map(fn (array $item) => abs((float) ($item['difference_quantity'] ?? 0)))->max()
                    : 0,
            ],
            [
                'key' => 'ordered_vs_delivered',
                'label' => 'Comandat vs livrat',
                'left' => $ordered,
                'right' => $delivered,
                'is_applicable' => $hasDelivered,
                'delta' => $hasDelivered && $delivered !== null ? abs($ordered - $delivered) : 0,
            ],
            [
                'key' => 'delivered_vs_pump',
                'label' => 'Livrat vs pompa',
                'left' => $delivered,
                'right' => $pump,
                'is_applicable' => $hasDelivered && $hasPump,
                'delta' => $hasDelivered && $hasPump && $delivered !== null && $pump !== null ? abs($delivered - $pump) : 0,
            ],
            [
                'key' => 'pump_vs_consumed',
                'label' => 'Pompa vs consum',
                'left' => $pump,
                'right' => $consumed,
                'is_applicable' => $hasPump && $hasConsumed,
                'delta' => $hasPump && $hasConsumed && $pump !== null && $consumed !== null ? abs($pump - $consumed) : 0,
            ],
        ];

        $checks = collect($checks)
            ->map(function (array $item) use ($tolerance) {
                $item['is_blocking'] = ($item['is_applicable'] ?? false) === true && (float) $item['delta'] > $tolerance;

                return $item;
            })
            ->values()
            ->all();

        return [
            'tolerance' => $tolerance,
            'checks' => $checks,
            'is_blocked' => collect($checks)->contains(fn (array $item) => ($item['is_blocking'] ?? false) === true),
            'max_delta' => (float) collect($checks)->max('delta'),
            'delivered' => $delivered,
            'pump' => $pump,
            'consumed' => $consumed,
        ];
    }

    /**
     * @param Collection<int, ResourceConfirmation> $confirmations
     */
    public static function resolveLifecycleStatus(array $reconciliation, Collection $confirmations): string
    {
        if (($reconciliation['is_blocked'] ?? false) === true) {
            return 'blocked_payment';
        }

        if ($confirmations->contains(fn (ResourceConfirmation $item) => $item->status === 'rejected')) {
            return 'rejected';
        }

        $roleStatuses = $confirmations->keyBy('confirmation_role');

        $technicalRoles = ['site_manager', 'execution_manager', 'quality_manager'];
        $technicalComplete = collect($technicalRoles)
            ->every(fn (string $role) => ($roleStatuses->get($role)?->status ?? null) === 'confirmed');

        $anyTechnicalConfirmed = collect($technicalRoles)
            ->contains(fn (string $role) => ($roleStatuses->get($role)?->status ?? null) === 'confirmed');

        $financialStatus = $roleStatuses->get('financial_manager')?->status;

        if ($technicalComplete && $financialStatus === 'confirmed') {
            return 'approved';
        }

        if ($technicalComplete) {
            return 'financial_review';
        }

        if ($anyTechnicalConfirmed) {
            return 'verified';
        }

        return 'ordered';
    }
}
