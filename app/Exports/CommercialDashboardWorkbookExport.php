<?php

namespace App\Exports;

use App\Exports\Sheets\CollectionSheetExport;
use App\Support\CommercialLabels;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CommercialDashboardWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private array $payload,
    ) {
    }

    public function sheets(): array
    {
        return [
            new CollectionSheetExport('Rezumat', ['Indicator', 'Valoare'], $this->summaryRows()),
            new CollectionSheetExport('Funnel', ['Indicator', 'Valoare'], $this->simpleRows($this->payload['funnel'] ?? [], '', 'funnel')),
            new CollectionSheetExport('Conversii', ['Indicator', 'Valoare'], $this->simpleRows($this->payload['conversion'] ?? [], '%', 'conversion')),
            new CollectionSheetExport('Forecast', ['Indicator', 'Valoare'], $this->simpleRows($this->payload['forecast'] ?? [], 'RON', 'forecast')),
            new CollectionSheetExport('Riscuri', ['Firma', 'Plan', 'Scor risc', 'Nivel risc', 'Utilizatori activi', 'Gap onboarding', 'Semnal churn', 'Trial expira curand', 'Trial end'], $this->riskRows()),
            new CollectionSheetExport('Oportunitati', ['Status', 'Etapa comerciala', 'Utilizatori estimati', 'Personalizare', 'Plan recomandat', 'MRR potential'], $this->opportunityRows()),
            new CollectionSheetExport('Semnale', ['Firma', 'Status', 'Etapa comerciala', 'Utilizatori estimati', 'Personalizare', 'Data'], $this->signalRows()),
        ];
    }

    private function summaryRows(): Collection
    {
        return collect([
            ['Indicator' => 'Generat la', 'Valoare' => now()->toDateTimeString()],
            ['Indicator' => 'MRR total', 'Valoare' => (string) ($this->payload['kpis']['current_mrr'] ?? 0)],
            ['Indicator' => 'Firme platitoare', 'Valoare' => (string) ($this->payload['kpis']['tenants_paid'] ?? 0)],
            ['Indicator' => 'Firme trial', 'Valoare' => (string) ($this->payload['kpis']['tenants_trial'] ?? 0)],
            ['Indicator' => 'Trial la risc', 'Valoare' => (string) ($this->payload['kpis']['tenants_at_risk'] ?? 0)],
            ['Indicator' => 'Risc ridicat', 'Valoare' => (string) ($this->payload['riskOverview']['high_risk_count'] ?? 0)],
            ['Indicator' => 'Risc mediu', 'Valoare' => (string) ($this->payload['riskOverview']['medium_risk_count'] ?? 0)],
            ['Indicator' => 'Gap onboarding', 'Valoare' => (string) ($this->payload['riskOverview']['tenants_with_onboarding_gap'] ?? 0)],
            ['Indicator' => 'Semnal churn', 'Valoare' => (string) ($this->payload['riskOverview']['tenants_with_churn_signal'] ?? 0)],
        ]);
    }

    private function simpleRows(array $items, string $suffix = '', string $context = 'generic'): Collection
    {
        return collect($items)->map(function ($value, $key) use ($suffix, $context) {
            $normalized = (string) $value;
            if ($suffix !== '') {
                $normalized .= ' ' . $suffix;
            }

            return [
                'Indicator' => $this->labelIndicator((string) $key, $context),
                'Valoare' => $normalized,
            ];
        })->values();
    }

    private function riskRows(): Collection
    {
        return collect($this->payload['riskScoredTenants'] ?? [])->map(function (array $tenant) {
            return [
                'Firma' => $tenant['name'] ?? '',
                'Plan' => CommercialLabels::plan((string) ($tenant['billing_plan'] ?? '')),
                'Scor risc' => $tenant['risk_score'] ?? 0,
                'Nivel risc' => CommercialLabels::risk((string) ($tenant['risk_level'] ?? '')),
                'Utilizatori activi' => $tenant['active_memberships_count'] ?? 0,
                'Gap onboarding' => $tenant['onboarding_incomplete_memberships_count'] ?? 0,
                'Semnal churn' => !empty($tenant['churn_signal']) ? 'Da' : 'Nu',
                'Trial expira curand' => !empty($tenant['trial_expiring_soon']) ? 'Da' : 'Nu',
                'Trial end' => $tenant['trial_ends_at'] ?? '',
            ];
        })->values();
    }

    private function opportunityRows(): Collection
    {
        return collect($this->payload['topPipelineOpportunities'] ?? [])->map(function (array $item) {
            return [
                'Status' => CommercialLabels::status((string) ($item['status'] ?? '')),
                'Etapa comerciala' => CommercialLabels::stage((string) ($item['commercial_stage'] ?? '')),
                'Utilizatori estimati' => $item['estimated_users'] ?? '',
                'Personalizare' => $item['customization_scope_label'] ?? '',
                'Plan recomandat' => CommercialLabels::plan((string) ($item['recommended_plan'] ?? '')),
                'MRR potential' => $item['recommended_mrr'] ?? 0,
            ];
        })->values();
    }

    private function signalRows(): Collection
    {
        return collect($this->payload['recentCommercialSignals'] ?? [])->map(function (array $item) {
            return [
                'Firma' => $item['company_name'] ?? '',
                'Status' => CommercialLabels::status((string) ($item['status'] ?? '')),
                'Etapa comerciala' => CommercialLabels::stage((string) ($item['commercial_stage'] ?? '')),
                'Utilizatori estimati' => $item['estimated_users'] ?? '',
                'Personalizare' => $item['customization_scope_label'] ?? '',
                'Data' => $item['created_at'] ?? '',
            ];
        })->values();
    }

    private function labelIndicator(string $key, string $context): string
    {
        return match ($context) {
            'funnel' => match ($key) {
                'invited' => 'Invitate',
                'contacted' => 'Contactate',
                'demo_scheduled' => 'Demo programat',
                'trial_started' => 'Trial pornit',
                'closed_won' => 'Castigate',
                'closed_lost' => 'Pierdute',
                default => $key,
            },
            'conversion' => match ($key) {
                'pilot_to_demo' => 'Pilot -> Demo',
                'pilot_to_trial' => 'Pilot -> Trial',
                'pilot_to_paid' => 'Pilot -> Paid',
                default => $key,
            },
            'forecast' => match ($key) {
                'current_mrr' => 'MRR curent',
                'forecast_30_days' => 'Forecast 30 zile',
                'forecast_60_days' => 'Forecast 60 zile',
                'forecast_90_days' => 'Forecast 90 zile',
                default => $key,
            },
            default => $key,
        };
    }

}
