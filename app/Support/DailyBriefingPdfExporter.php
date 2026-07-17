<?php

namespace App\Support;

class DailyBriefingPdfExporter
{
    public static function buildSections(array $briefing): array
    {
        return [
            self::summarySection($briefing),
            self::timelineSection($briefing),
            self::teamsSection($briefing),
            self::subcontractorsSection($briefing),
            self::materialsSection($briefing),
            self::equipmentSection($briefing),
            self::documentsSection($briefing),
            self::tasksSection($briefing),
            self::blockersSection($briefing),
            self::recommendationsSection($briefing),
        ];
    }

    private static function summarySection(array $briefing): array
    {
        return [
            'name' => 'Rezumat',
            'headings' => ['Data', 'Risc', 'Rezumat'],
            'rows' => [[
                'Data' => $briefing['date'],
                'Risc' => $briefing['risk_label'],
                'Rezumat' => $briefing['summary'],
            ]],
        ];
    }

    private static function timelineSection(array $briefing): array
    {
        $rows = array_map(fn (array $entry) => [
            'Ora' => $entry['all_day'] ? 'Toata ziua' : $entry['time'],
            'Eveniment' => ($entry['blocked'] ? '[BLOCAT] ' : '') . $entry['label'],
        ], $briefing['timeline']);

        return ['name' => 'Cronologie', 'headings' => ['Ora', 'Eveniment'], 'rows' => $rows];
    }

    private static function teamsSection(array $briefing): array
    {
        $rows = array_map(fn (array $team) => [
            'Echipa' => $team['team_name'],
            'Etapa' => $team['phase_name'] ?? '-',
            'Necesar' => $team['workers_needed'],
            'Alocati' => $team['workers_assigned'],
            'Status' => $team['confirmation_status'],
        ], $briefing['teams']);

        return ['name' => 'Echipe programate azi', 'headings' => ['Echipa', 'Etapa', 'Necesar', 'Alocati', 'Status'], 'rows' => $rows];
    }

    private static function subcontractorsSection(array $briefing): array
    {
        $rows = array_map(fn (array $sub) => [
            'Subcontractor' => $sub['contractor_name'],
            'Etapa' => $sub['phase_name'],
            'Status etapa' => $sub['phase_status'],
            'Confirmare' => $sub['confirmation_status'],
        ], $briefing['subcontractors']);

        return ['name' => 'Subcontractori programati azi', 'headings' => ['Subcontractor', 'Etapa', 'Status etapa', 'Confirmare'], 'rows' => $rows];
    }

    private static function materialsSection(array $briefing): array
    {
        $rows = array_map(fn (array $material) => [
            'Material' => $material['material_name'],
            'Furnizor' => $material['supplier_name'] ?? '-',
            'Cantitate' => trim(($material['ordered_quantity'] ?? '') . ' ' . ($material['ordered_unit'] ?? '')),
            'Status' => $material['status_label'],
        ], $briefing['materials']);

        return ['name' => 'Materiale cu livrare azi', 'headings' => ['Material', 'Furnizor', 'Cantitate', 'Status'], 'rows' => $rows];
    }

    private static function equipmentSection(array $briefing): array
    {
        $rows = array_map(fn (array $item) => [
            'Utilaj' => $item['equipment_name'],
            'Etapa' => $item['phase_name'] ?? '-',
            'Cantitate' => $item['quantity'],
        ], $briefing['equipment']);

        return ['name' => 'Utilaje rezervate azi', 'headings' => ['Utilaj', 'Etapa', 'Cantitate'], 'rows' => $rows];
    }

    private static function documentsSection(array $briefing): array
    {
        $rows = array_map(fn (array $doc) => [
            'Document' => $doc['title'],
            'Tip' => $doc['item_type_label'],
            'Status' => $doc['status_label'],
            'Scadenta' => $doc['due_date'],
        ], $briefing['documents']);

        return ['name' => 'Documente cu scadenta azi', 'headings' => ['Document', 'Tip', 'Status', 'Scadenta'], 'rows' => $rows];
    }

    private static function tasksSection(array $briefing): array
    {
        $rows = array_map(fn (array $task) => [
            'Task' => $task['title'],
            'Status' => $task['status'],
            'Etapa' => $task['phase_name'] ?? '-',
        ], $briefing['tasks']);

        return ['name' => 'Task-uri critice azi', 'headings' => ['Task', 'Status', 'Etapa'], 'rows' => $rows];
    }

    private static function blockersSection(array $briefing): array
    {
        $rows = array_map(fn (string $blocker) => ['Blocaj' => $blocker], $briefing['blockers']);

        return ['name' => 'Blocaje', 'headings' => ['Blocaj'], 'rows' => $rows];
    }

    private static function recommendationsSection(array $briefing): array
    {
        $rows = array_map(fn (array $recommendation) => ['Recomandare' => $recommendation['message']], $briefing['recommendations']);

        return ['name' => 'Recomandari', 'headings' => ['Recomandare'], 'rows' => $rows];
    }
}
