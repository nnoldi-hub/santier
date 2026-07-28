<?php

namespace App\Support;

use App\Models\AppSetting;

class BrochureContent
{
    /**
     * Text implicit al brosurii PDF, folosit cand nu exista inca nicio
     * personalizare salvata prin ecranul de admin.
     */
    public static function defaults(): array
    {
        return [
            'cover_title' => 'Șantierul devine clar.',
            'cover_subtitle' => 'Broșura de prezentare Modulia - platforma de management de șantier pentru antreprenori și firme de construcții.',
            'closing_title' => 'Hai sa vedem daca se potriveste firmei tale.',
            'closing_text' => 'Pornesti in 10 minute cu wizard-ul de onboarding, un proiect demo si raport exportabil pentru management.',
            'pain_points' => [
                ['title' => 'Taskuri pierdute', 'text' => 'Backlog clar pe proiect, responsabil si deadline, fara mesaje imprastiate.'],
                ['title' => 'Etape intarziate', 'text' => 'Monitorizare zilnica a progresului, interventie inainte sa intarzie santierul.'],
                ['title' => 'Lipsa vizibilitate', 'text' => 'Dashboard unificat pentru status, cost, defecte si decizii operative.'],
                ['title' => 'Defecte deschise', 'text' => 'Snag list cu prioritati, asignare si termen, pana la rezolvare.'],
                ['title' => 'Oferte dispersate', 'text' => 'Versionare devize si istoric centralizat, usor de comparat.'],
                ['title' => 'Rapoarte lente', 'text' => 'Export XLSX/PDF managerial cu filtre avansate in cateva secunde.'],
            ],
            'features' => [
                ['title' => 'Ofertare inteligenta', 'text' => 'Oferte generate rapid, cu sabloane, costuri reale si timeline automat.'],
                ['title' => 'Proiecte & WBS', 'text' => 'Structura clara pe etape, taskuri si progres.'],
                ['title' => 'Taskuri & Progres', 'text' => 'Actualizari, poze, rapoarte si responsabilitati intr-un singur loc.'],
                ['title' => 'Calendar & Planificare', 'text' => 'Planificare vizuala, clara, fara haos.'],
                ['title' => 'Documente', 'text' => 'Documente ordonate, aprobari, versiuni, exporturi.'],
                ['title' => 'Financiar & Cost Tracking', 'text' => 'Costuri reale, materiale, manopera, profit, devize.'],
                ['title' => 'AI Tools', 'text' => 'Generare oferte, analiza proiecte si riscuri din timp.'],
                ['title' => 'Roluri & Permisiuni Enterprise', 'text' => 'Superadmin, Admin firma, roluri custom, permisiuni granulare.'],
            ],
            'how_it_works' => [
                ['title' => 'Configurezi proiectul in 10 minute', 'text' => 'Sabloane de etape, responsabili si termene.'],
                ['title' => 'Executi si urmaresti progresul zilnic', 'text' => 'Taskuri, defecte si costuri intr-un singur tablou de bord.'],
                ['title' => 'Raportezi clar catre management', 'text' => 'Export PDF/XLSX, riscuri si decizii pe date reale.'],
            ],
        ];
    }

    /**
     * Continutul curent al brosurii - defaults() suprascrise de orice
     * personalizare salvata prin AppSetting (tenant_id 0, platforma).
     */
    public static function current(): array
    {
        $defaults = self::defaults();

        $stored = AppSetting::allWithDefaults([
            'brochure_cover_title' => $defaults['cover_title'],
            'brochure_cover_subtitle' => $defaults['cover_subtitle'],
            'brochure_closing_title' => $defaults['closing_title'],
            'brochure_closing_text' => $defaults['closing_text'],
            'brochure_pain_points' => json_encode($defaults['pain_points']),
            'brochure_features' => json_encode($defaults['features']),
            'brochure_how_it_works' => json_encode($defaults['how_it_works']),
        ]);

        return [
            'cover_title' => $stored['brochure_cover_title'],
            'cover_subtitle' => $stored['brochure_cover_subtitle'],
            'closing_title' => $stored['brochure_closing_title'],
            'closing_text' => $stored['brochure_closing_text'],
            'pain_points' => self::decodeList($stored['brochure_pain_points'], $defaults['pain_points']),
            'features' => self::decodeList($stored['brochure_features'], $defaults['features']),
            'how_it_works' => self::decodeList($stored['brochure_how_it_works'], $defaults['how_it_works']),
        ];
    }

    public static function persist(array $values): void
    {
        AppSetting::setValues([
            'brochure_cover_title' => (string) ($values['brochure_cover_title'] ?? ''),
            'brochure_cover_subtitle' => (string) ($values['brochure_cover_subtitle'] ?? ''),
            'brochure_closing_title' => (string) ($values['brochure_closing_title'] ?? ''),
            'brochure_closing_text' => (string) ($values['brochure_closing_text'] ?? ''),
            'brochure_pain_points' => json_encode(self::normalizeList($values['brochure_pain_points'] ?? [])),
            'brochure_features' => json_encode(self::normalizeList($values['brochure_features'] ?? [])),
            'brochure_how_it_works' => json_encode(self::normalizeList($values['brochure_how_it_works'] ?? [])),
        ]);
    }

    private static function decodeList(string $json, array $fallback): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : $fallback;
    }

    private static function normalizeList(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                $title = trim((string) ($item['title'] ?? ''));
                $text = trim((string) ($item['text'] ?? ''));

                if ($title === '' && $text === '') {
                    return null;
                }

                return ['title' => $title, 'text' => $text];
            })
            ->filter()
            ->values()
            ->all();
    }
}
