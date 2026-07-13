<?php

namespace App\Exports;

use App\Exports\Sheets\CollectionSheetExport;
use App\Support\ExportDatasetBuilder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EnterpriseWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private array $exportTypes,
        private array $filters,
        private array $branding,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];

        $summaryRows = collect([
            [
                'Companie' => $this->branding['company_name'] ?? 'Santier',
                'Email' => $this->branding['company_email'] ?? '',
                'Telefon' => $this->branding['company_phone'] ?? '',
                'Generat la' => now()->toDateTimeString(),
                'Filtre' => json_encode($this->filters, JSON_UNESCAPED_UNICODE),
            ],
        ]);

        $sheets[] = new CollectionSheetExport('Branding', ['Companie', 'Email', 'Telefon', 'Generat la', 'Filtre'], $summaryRows);

        foreach ($this->exportTypes as $type) {
            $dataset = ExportDatasetBuilder::build($type, $this->filters);
            $rows = $this->normalizeRows($type, $dataset['rows']);

            if ($rows->isEmpty()) {
                $rows = collect([['Info' => 'Nu exista date pentru filtrele selectate']]);
            }

            $headings = array_keys((array) $rows->first());
            $title = $dataset['meta']['title'] ?? ucfirst($type);

            $sheets[] = new CollectionSheetExport($title, $headings, $rows);
        }

        return $sheets;
    }

    private function normalizeRows(string $type, Collection $rows): Collection
    {
        return $rows->map(function ($item) use ($type) {
            return match ($type) {
                'projects' => [
                    'ID' => $item->id,
                    'Nume' => $item->name,
                    'Client' => $item->client?->name,
                    'Status' => $item->status,
                    'Start' => optional($item->start_date)->format('Y-m-d'),
                    'End' => optional($item->end_date)->format('Y-m-d'),
                    'Buget' => $item->total_budget,
                    'Adresa' => $item->address,
                ],
                'quotes' => [
                    'ID' => $item->id,
                    'Proiect' => $item->project?->name,
                    'Versiune' => $item->version,
                    'Titlu' => $item->title,
                    'Status' => $item->status,
                    'Valid pana la' => optional($item->valid_until)->format('Y-m-d'),
                    'Total net' => $item->total_net,
                    'Total TVA' => $item->total_tva,
                    'Total brut' => $item->total_gross,
                ],
                'materials' => [
                    'ID' => $item->id,
                    'Cod' => $item->code,
                    'Nume' => $item->name,
                    'Categorie' => $item->category,
                    'UM' => $item->unit,
                    'Pret unitar' => $item->unit_price,
                    'Furnizor' => $item->supplier,
                    'Activ' => $item->active ? 'Da' : 'Nu',
                ],
                'resource-comparison' => [
                    'ID comanda' => $item['order_id'] ?? null,
                    'Proiect' => $item['project'] ?? null,
                    'Etapa' => $item['phase'] ?? null,
                    'Tip resursa' => $item['resource_label'] ?? null,
                    'Material / utilaj' => $item['material'] ?? null,
                    'Cod material' => $item['material_code'] ?? null,
                    'Furnizor' => $item['supplier'] ?? null,
                    'Transportator' => $item['carrier'] ?? null,
                    'Comandat' => $item['ordered_quantity'] ?? null,
                    'Livrat declarat' => $item['declared_quantity'] ?? null,
                    'Receptionat' => $item['received_quantity'] ?? null,
                    'Consum' => $item['consumed_quantity'] ?? null,
                    'Returnat' => $item['returned_quantity'] ?? null,
                    'Diferenta comandat vs receptionat' => $item['received_delta'] ?? null,
                    'Linkuri documente' => $item['document_links_count'] ?? null,
                    'Avize livrare' => $item['delivery_notes_count'] ?? null,
                    'Qty din documente' => $item['document_delivered_quantity'] ?? null,
                    'Dif. din documente' => $item['document_difference_quantity'] ?? null,
                    'Status' => $item['status'] ?? null,
                    'Data livrare' => $item['delivery_date'] ?? null,
                    'Responsabil' => $item['responsible'] ?? null,
                    'Ultima livrare' => $item['latest_delivery_at'] ?? null,
                    'Observatii' => $item['notes'] ?? null,
                ],
                'material-timeline' => [
                    'Proiect' => $item['project'] ?? null,
                    'Etapa' => $item['phase'] ?? null,
                    'Material' => $item['material'] ?? null,
                    'Data eveniment' => $item['event_date'] ?? null,
                    'Tip eveniment' => $item['event_type'] ?? null,
                    'Actor' => $item['actor'] ?? null,
                    'Cantitate' => $item['quantity'] ?? null,
                    'UM' => $item['unit'] ?? null,
                    'Numar document' => $item['document_number'] ?? null,
                    'Status comanda' => $item['order_status'] ?? null,
                    'Observatii' => $item['notes'] ?? null,
                ],
                'equipment-consumption' => [
                    'Rezervare ID' => $item['reservation_id'] ?? null,
                    'Proiect' => $item['project'] ?? null,
                    'Etapa' => $item['phase'] ?? null,
                    'Utilaj' => $item['equipment'] ?? null,
                    'Tip utilaj' => $item['equipment_type'] ?? null,
                    'Disponibilitate' => $item['availability_status'] ?? null,
                    'Cantitate' => $item['quantity'] ?? null,
                    'Start' => $item['usage_start'] ?? null,
                    'End' => $item['usage_end'] ?? null,
                    'Zile' => $item['days'] ?? null,
                    'Cost/ora' => $item['hourly_cost'] ?? null,
                    'Cost estimat' => $item['estimated_cost'] ?? null,
                    'Consum material pe etapa' => $item['phase_material_consumed_quantity'] ?? null,
                    'Nr comenzi material pe etapa' => $item['phase_material_orders_count'] ?? null,
                ],
                'teams' => [
                    'Team ID' => $item['team_id'] ?? null,
                    'Echipa' => $item['team_name'] ?? null,
                    'Lider' => $item['leader'] ?? null,
                    'Membru' => $item['member'] ?? null,
                    'Rol' => $item['member_role'] ?? null,
                    'Specialitate' => $item['specialty'] ?? null,
                    'Activa' => ($item['active'] ?? false) ? 'Da' : 'Nu',
                    'Nr alocari' => $item['assignments_count'] ?? null,
                    'Proiecte' => $item['projects'] ?? null,
                ],
                'tasks' => [
                    'ID' => $item->id,
                    'Proiect' => $item->project?->name,
                    'Etapa' => $item->phase?->name,
                    'Titlu' => $item->title,
                    'Status' => $item->status,
                    'Prioritate' => $item->priority,
                    'Responsabil' => $item->assignee?->name,
                    'Deadline' => optional($item->deadline)->format('Y-m-d'),
                ],
                'defects' => [
                    'ID' => $item->id,
                    'Proiect' => $item->project?->name,
                    'Etapa' => $item->phase?->name,
                    'Titlu' => $item->title,
                    'Status' => $item->status,
                    'Prioritate' => $item->priority,
                    'Responsabil' => $item->assignee?->name,
                    'Locatie' => $item->location,
                    'Due date' => optional($item->due_date)->format('Y-m-d'),
                ],
                'wbs' => [
                    'ID' => $item['id'] ?? null,
                    'Proiect' => $item['project'] ?? null,
                    'Etapa' => $item['name'] ?? null,
                    'Nivel WBS' => $item['level'] ?? null,
                    'Path WBS' => $item['wbs_path'] ?? null,
                    'Parinte' => $item['parent'] ?? null,
                    'Status' => $item['status'] ?? null,
                    'Progres %' => $item['progress_pct'] ?? null,
                    'Contractor' => $item['contractor'] ?? null,
                    'Start' => $item['start_date'] ?? null,
                    'End' => $item['end_date'] ?? null,
                ],
                'equipment' => [
                    'Rezervare ID' => $item['reservation_id'] ?? null,
                    'Proiect' => $item['project'] ?? null,
                    'Etapa' => $item['phase'] ?? null,
                    'Status etapa' => $item['phase_status'] ?? null,
                    'Utilaj' => $item['equipment'] ?? null,
                    'Tip utilaj' => $item['equipment_type'] ?? null,
                    'Furnizor' => $item['supplier'] ?? null,
                    'Disponibilitate' => $item['availability_status'] ?? null,
                    'Cantitate' => $item['quantity'] ?? null,
                    'Start' => $item['usage_start'] ?? null,
                    'End' => $item['usage_end'] ?? null,
                    'Zile' => $item['days'] ?? null,
                    'Cost / ora' => $item['hourly_cost'] ?? null,
                    'Cost estimat' => $item['estimated_cost'] ?? null,
                ],
                'documents' => [
                    'ID' => $item->id,
                    'Titlu' => $item->title,
                    'Tip' => $item->type,
                    'Proiect' => $item->project?->name,
                    'Etapa' => $item->stage?->name,
                    'Contractor' => $item->contractor?->name,
                    'Status plata' => $item->payment_status,
                    'Suma' => $item->amount,
                    'Data emitere' => optional($item->issued_at)->format('Y-m-d'),
                    'Fisier' => $item->file_name,
                ],
                'stage-reports' => [
                    'ID' => $item->id,
                    'Proiect' => $item->stage?->project?->name,
                    'Etapa' => $item->stage?->name,
                    'Contractor' => $item->contractor?->name,
                    'Raportat de' => $item->creator?->name,
                    'Data raport' => optional($item->report_date)->format('Y-m-d'),
                    'Progres %' => $item->progress_pct,
                    'Activitati' => $item->activities,
                    'Probleme' => $item->issues,
                ],
                'stage-tasks' => [
                    'ID' => $item->id,
                    'Proiect' => $item->stage?->project?->name,
                    'Etapa' => $item->stage?->name,
                    'Titlu' => $item->title,
                    'Status' => $item->status,
                    'Tip responsabil' => $item->assignee_type,
                    'Responsabil' => match ($item->assignee_type) {
                        'user' => $item->userAssignee?->name,
                        'team' => $item->teamAssignee?->name,
                        'contractor' => $item->contractorAssignee?->name,
                        default => null,
                    },
                    'Deadline' => optional($item->deadline)->format('Y-m-d H:i'),
                ],
                'stage-progress' => [
                    'ID etapa' => $item['phase_id'] ?? null,
                    'Proiect' => $item['project'] ?? null,
                    'Etapa' => $item['phase'] ?? null,
                    'Parinte' => $item['parent'] ?? null,
                    'Contractor' => $item['contractor'] ?? null,
                    'Status' => $item['status'] ?? null,
                    'Progres %' => $item['progress_pct'] ?? null,
                    'Start' => $item['start_date'] ?? null,
                    'End' => $item['end_date'] ?? null,
                    'Ordine' => $item['order'] ?? null,
                ],
                'costs' => $item,
                default => is_array($item) ? $item : (array) $item,
            };
        });
    }
}
