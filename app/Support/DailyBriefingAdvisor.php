<?php

namespace App\Support;

use Illuminate\Support\Collection;

class DailyBriefingAdvisor
{
    /**
     * @param array{teams: Collection, subcontractors: Collection, materials: Collection, documents: Collection, tasks: Collection} $sections
     * @return array<int, array{type: string, message: string}>
     */
    public static function suggest(array $sections): array
    {
        $recommendations = [];

        foreach ($sections['teams'] as $team) {
            if ($team['confirmation_status'] === 'risc') {
                $recommendations[] = [
                    'type' => 'team_understaffed',
                    'message' => "Echipa {$team['team_name']} are doar {$team['workers_assigned']}/{$team['workers_needed']} muncitori confirmati azi - verifica disponibilitatea.",
                ];
            }
        }

        foreach ($sections['materials'] as $material) {
            if ($material['status'] === 'blocked_payment') {
                $recommendations[] = [
                    'type' => 'material_payment_blocked',
                    'message' => "Materialul \"{$material['material_name']}\" are livrare azi dar comanda e blocata la plata - confirma cu furnizorul {$material['supplier_name']}.",
                ];
            }
        }

        foreach ($sections['documents'] as $document) {
            if (in_array($document['status'], ['expired', 'missing'], true)) {
                $recommendations[] = [
                    'type' => 'document_at_risk',
                    'message' => "Documentul \"{$document['title']}\" ({$document['item_type_label']}) are scadenta azi si statusul {$document['status_label']} - rezolva inainte de inceperea lucrarilor.",
                ];
            }
        }

        if ($sections['teams']->isEmpty() && $sections['subcontractors']->isEmpty()) {
            $recommendations[] = [
                'type' => 'no_crew_today',
                'message' => 'Nicio echipa sau subcontractor alocat azi pe acest proiect - verifica daca lipseste o planificare.',
            ];
        }

        foreach ($sections['tasks'] as $task) {
            if ($task['status'] === 'blocked') {
                $recommendations[] = [
                    'type' => 'stage_task_blocked',
                    'message' => "Taskul \"{$task['title']}\" este blocat cu termen azi - identifica blocajul inainte sa afecteze etapa" . ($task['phase_name'] ? " \"{$task['phase_name']}\"" : '') . '.',
                ];
            }
        }

        return $recommendations;
    }
}
