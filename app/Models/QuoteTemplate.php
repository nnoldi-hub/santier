<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'source_quote_id',
        'source_project_id',
        'template_payload',
        'usage_count',
        'quality_score',
        'is_recommended',
        'last_used_at',
        'created_by',
    ];

    protected $casts = [
        'template_payload' => 'array',
        'is_recommended' => 'boolean',
        'quality_score' => 'decimal:2',
        'last_used_at' => 'datetime',
    ];

    public function sourceQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'source_quote_id');
    }

    public function sourceProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'source_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeRecommended(Builder $query, int $tenantId): Builder
    {
        return $query
            ->where('tenant_id', $tenantId)
            ->where('is_recommended', true)
            ->orderByDesc('quality_score')
            ->orderByDesc('usage_count')
            ->orderByDesc('last_used_at')
            ->orderByDesc('updated_at');
    }

    public static function upsertFromQuote(Quote $quote, ?int $createdBy = null, ?string $name = null): self
    {
        $quote->loadMissing(['project:id,name,status', 'items']);

        $payload = self::buildPayloadFromQuote($quote);
        $qualityScore = self::qualityScoreFromQuote($quote, $payload);

        $template = self::query()->firstOrNew([
            'tenant_id' => (int) $quote->tenant_id,
            'source_project_id' => (int) $quote->project_id,
        ]);

        $template->fill([
            'name' => $name ?: ('Template - ' . ($quote->project?->name ?? ('Proiect #' . $quote->project_id))),
            'source_quote_id' => $quote->id,
            'template_payload' => $payload,
            'quality_score' => $qualityScore,
            'is_recommended' => true,
            'created_by' => $createdBy,
        ]);

        $template->save();

        return $template;
    }

    public function markUsed(): void
    {
        $this->forceFill([
            'usage_count' => (int) $this->usage_count + 1,
            'last_used_at' => now(),
        ])->save();
    }

    private static function qualityScoreFromQuote(Quote $quote, array $payload): float
    {
        $items = is_array($payload['stages'] ?? null)
            ? collect($payload['stages'])->sum(fn ($stage) => is_array($stage['items'] ?? null) ? count($stage['items']) : 0)
            : 0;

        $hasSmartData = is_array($payload['smart_inputs'] ?? null) && collect($payload['smart_inputs'])->sum(fn ($v) => (float) $v) > 0;
        $statusBonus = $quote->status === 'accepted' ? 20 : ($quote->status === 'sent' ? 10 : 0);

        $base = min($items * 1.5, 50);
        $metaBonus = $hasSmartData ? 10 : 0;

        return round(min($base + $metaBonus + $statusBonus + 20, 100), 2);
    }

    private static function buildPayloadFromQuote(Quote $quote): array
    {
        $meta = is_array($quote->meta) ? $quote->meta : [];

        $stagesMeta = is_array($meta['stages'] ?? null) ? $meta['stages'] : [];
        $smartInputs = is_array($meta['smart_inputs'] ?? null) ? $meta['smart_inputs'] : [];
        $indirectCosts = is_array($meta['indirect_costs'] ?? null) ? $meta['indirect_costs'] : [];
        $optionalFeatures = is_array($meta['optional_features'] ?? null) ? $meta['optional_features'] : [];

        $itemsByStage = $quote->items
            ->groupBy(fn ($item) => trim((string) ($item->stage_name ?? 'General')))
            ->map(fn ($rows) => $rows->map(fn ($item) => [
                'item_type' => (string) $item->item_type,
                'reference_id' => $item->reference_id ? (int) $item->reference_id : null,
                'name' => (string) $item->name,
                'unit' => (string) $item->unit,
                'quantity' => (float) $item->quantity,
                'cost_unit_price' => (float) $item->cost_unit_price,
                'sell_unit_price' => (float) $item->sell_unit_price,
            ])->values()->all())
            ->all();

        $stages = collect($stagesMeta)
            ->map(function ($stageMeta, $index) use ($itemsByStage) {
                $stageName = (string) ($stageMeta['name'] ?? ('Etapa ' . ((int) $index + 1)));

                return [
                    'name' => $stageName,
                    'duration_days' => (int) ($stageMeta['duration_days'] ?? 1),
                    'items' => is_array($itemsByStage[$stageName] ?? null) ? $itemsByStage[$stageName] : [],
                ];
            })
            ->values()
            ->all();

        if ($stages === []) {
            foreach ($itemsByStage as $stageName => $items) {
                $stages[] = [
                    'name' => (string) $stageName,
                    'duration_days' => 1,
                    'items' => is_array($items) ? $items : [],
                ];
            }
        }

        return [
            'project_type' => (string) ($meta['project_type'] ?? self::inferProjectType($quote)),
            'title' => (string) $quote->title,
            'status' => 'draft',
            'valid_until' => null,
            'discount_pct' => (float) $quote->discount_pct,
            'tva_pct' => (float) $quote->tva_pct,
            'min_margin_pct' => (float) ($meta['min_margin_pct'] ?? 12),
            'notes' => (string) ($quote->notes ?? ''),
            'package_tier' => (string) ($meta['package_tier'] ?? 'standard'),
            'smart_inputs' => $smartInputs,
            'indirect_costs' => $indirectCosts,
            'optional_features' => $optionalFeatures,
            'stages' => $stages,
            'source' => [
                'project_id' => (int) $quote->project_id,
                'quote_id' => (int) $quote->id,
            ],
        ];
    }

    private static function inferProjectType(Quote $quote): string
    {
        $haystack = mb_strtolower(trim(
            (string) ($quote->project?->name ?? '')
            . ' '
            . (string) ($quote->title ?? '')
        ));

        if (str_contains($haystack, '2 camere')) return 'apartment_2_rooms';
        if (str_contains($haystack, '3 camere')) return 'apartment_3_rooms';
        if (str_contains($haystack, 'casa')) return 'house';
        if (str_contains($haystack, 'comercial')) return 'commercial_space';
        if (str_contains($haystack, 'completa')) return 'full_renovation';
        if (str_contains($haystack, 'partiala')) return 'partial_renovation';

        return 'full_renovation';
    }
}
