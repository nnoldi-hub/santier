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
            'cover_subtitle' => 'Platforma de management de șantier care aduce proiectele, echipele, costurile și documentele intr-un singur loc - pentru firme de construcții și antreprenori care vor control, nu haos.',
            'closing_title' => 'Programeaza un demo si vezi Modulia pe proiectele tale.',
            'closing_text' => 'In 10 minute configurezi primul proiect si primesti un raport managerial exportabil. Fara instalare, fara contract pe termen lung, fara costuri ascunse.',
            'pain_points' => [
                ['title' => 'Taskuri pierdute in mesaje', 'text' => 'Backlog clar pe proiect, responsabil si termen - fara WhatsApp, Excel si notite imprastiate.'],
                ['title' => 'Etape care intarzie tacit', 'text' => 'Progres urmarit zilnic, pe etape, cu alerte inainte ca intarzierea sa afecteze termenul final.'],
                ['title' => 'Decizii luate pe presupuneri', 'text' => 'Un singur dashboard cu status real, cost si defecte deschise - decizii pe date, nu pe impresii.'],
                ['title' => 'Defecte care se pierd pe drum', 'text' => 'Snag list cu poze, prioritate, responsabil si termen, pana la inchiderea confirmata.'],
                ['title' => 'Oferte greu de tinut sub control', 'text' => 'Versionare automata a devizelor si istoric centralizat, usor de comparat si de trimis clientului.'],
                ['title' => 'Rapoarte facute manual, ore intregi', 'text' => 'Export XLSX/PDF managerial, gata de trimis catre investitor sau client, in cateva secunde.'],
            ],
            'features' => [
                ['title' => 'Ofertare inteligenta', 'text' => 'Devize generate rapid din sabloane si costuri reale, cu timeline automat de executie.'],
                ['title' => 'Proiecte & WBS', 'text' => 'Structura clara pe etape de lucru, taskuri si progres masurabil, de la fundatie la predare.'],
                ['title' => 'Taskuri & Progres zilnic', 'text' => 'Actualizari cu poze, rapoarte de progres si responsabilitati intr-un singur loc, vizibile pentru toata echipa.'],
                ['title' => 'Calendar & Planificare', 'text' => 'Planificare vizuala pentru echipe, subcontractori si utilaje - fara suprapuneri si fara surprize.'],
                ['title' => 'Documente centralizate', 'text' => 'Contracte, procese verbale si aprobari, ordonate, versionate si usor de regasit oricand.'],
                ['title' => 'Financiar & Cost Tracking', 'text' => 'Costuri reale de materiale si manopera, profit pe proiect si devize, actualizate automat.'],
                ['title' => 'AI Tools', 'text' => 'Generare rapida de oferte si analiza timpurie a riscurilor, direct din datele proiectului.'],
                ['title' => 'Roluri & Permisiuni Enterprise', 'text' => 'Superadmin, admin de firma, roluri custom si permisiuni granulare pentru control total al accesului.'],
            ],
            'how_it_works' => [
                ['title' => '1. Configurezi proiectul in 10 minute', 'text' => 'Sabloane de etape, responsabili si termene, gata de folosit din prima zi.'],
                ['title' => '2. Executi si urmaresti progresul zilnic', 'text' => 'Taskuri, defecte si costuri reale, actualizate live intr-un singur tablou de bord.'],
                ['title' => '3. Raportezi clar catre management', 'text' => 'Export PDF/XLSX cu riscuri si decizii fundamentate pe date reale, nu pe presupuneri.'],
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
