# Modul "Memento Zilnic" - plan de dezvoltare pe faze

Sursa initiala: cererea utilizatorului de a genera automat, per proiect, o lista
de verificare zilnica (echipe, subcontractori, materiale, utilaje, documente,
task-uri, blocaje, recomandari), trimisa prin email si notificare in-app la o ora
configurabila. WhatsApp a fost cerut explicit dar amanat (cont Twilio/Meta necesar,
ca la Stripe).

## Faze

| # | Faza | Status |
|---|------|--------|
| 1 | Fundatie + automatizare completa (agregare, pagina, setari, cron, email, notificare in-app) | **Facut** |
| 1b | v2.0 - rezumat, risc, cronologie, export PDF, istoric | **Facut** |
| 2 | Canal WhatsApp (Twilio sau Meta Cloud API - alegere ramasa la utilizator) | Neinceput |

## Faza 1 - Fundatie + automatizare completa (Facut, 2026-07-17)

- **Cercetare in cod inainte de implementare** (2 agenti Explore + verificare
  directa a fisierelor critice): toate datele brute necesare exista deja, fara
  nicio migratie pe tabelele de executie - `PhaseTeamAssignment` (echipe),
  `ProjectPhase.contractor_id` (subcontractori), `ResourceOrder` (materiale),
  `StageEquipment` (utilaje), `SiteCompliancePlan` (documente, din modulul
  "Organizare Șantier"), `Task`/`StageTask` (task-uri). Niciun model nu are un
  camp real de confirmare - statusul "confirmat/neconfirmat/risc" e un **proxy
  calculat** din campurile existente (ex: `workers_assigned >= workers_needed`,
  `ResourceOrder.status`), fara actiuni noi de confirmare (decizie confirmata cu
  utilizatorul).
- `App\Support\DailyBriefingBuilder` (nou) - motor de agregare static, `build(Project, ?Carbon): array`,
  produce cele 7 sectiuni + blocaje. Interogarea de interval de date "azi" (deja
  folosita de 3 ori in `routes/web.php` pentru dashboard) extrasa intr-un helper
  privat unic `dateWindowQuery()` in loc de o a patra copiere.
- `App\Support\DailyBriefingAdvisor` (nou) - recomandari, euristici locale pe
  `Collection` (acelasi tipar ca `SitePlanningAIAdvisor`) - **fara nicio
  integrare LLM externa**, confirmat prin cod ca nu exista asa ceva nicaieri in
  aplicatie.
- Tabel nou `project_daily_briefing_settings` (`App\Models\ProjectDailyBriefingSetting`) -
  setari per proiect: activat, ora trimiterii, destinatari (`recipient_user_ids`
  JSON), nivel de detaliu, canale (`channels` JSON, `whatsapp` prezent dar
  neutilizat - fara migratie noua cand se adauga Faza 2).
- `App\Http\Controllers\DailyBriefingController` (nou) - `show()`/`updateSettings()`,
  acelasi tipar de scoping tenant ca `SiteOrganizationController`.
- Pagina noua `resources/js/Pages/DailyBriefing/Show.vue` (rute
  `daily-briefing.show`/`daily-briefing.settings.update`, `projects/{project}/memento`) -
  doua sub-taburi ("Astazi": cele 7 sectiuni + blocaje + recomandari; "Setari":
  activare, ora, destinatari, nivel detaliu, canale). Buton nou "Memento Zilnic"
  pe `Projects/Show.vue`, langa "Organizare Șantier".
- Card nou pe dashboard (`routes/web.php` + `Dashboard.vue`) - proiectele active
  cu blocaje azi, link direct catre memento.
- `App\Mail\DailySiteBriefingMail` (nou, acelasi tipar ca `QuoteSentMail`) +
  view `resources/views/emails/daily-briefing.blade.php`.
- **Notificare in-app: refolosita `App\Notifications\OperationalReminderNotification`
  existenta** (generica, `via(): ['database']`) - fara clasa noua, doar un nou
  `event: 'daily_briefing'` inregistrat si in `NotificationCenterController::eventOptions`.
  Bell-ul si `Account/Notifications.vue` functioneaza fara nicio modificare.
- `App\Console\Commands\SendDailySiteBriefingCommand` (`briefing:send-daily`,
  `routes/console.php` -> `everyFiveMinutes()`) - tipar similar
  `SendOperationalRemindersCommand`: verifica `send_time` per proiect, trimite pe
  canalele activate, garda idempotenta prin `last_sent_date` (sigur la rulari
  suprapuse).
- Teste (rulate real local, nu doar `php -l` - vezi nota de mai jos):
  `tests/Feature/DailyBriefingBuilderTest.php` (agregare completa + caz gol),
  `tests/Feature/DailyBriefingSettingsTest.php` (CRUD setari, validare, izolare
  tenant), `tests/Feature/SendDailySiteBriefingCommandTest.php` (trimitere +
  idempotenta la rulari duble + proiect dezactivat sarit). 8 teste, 39 asertii,
  toate trecute.
- **Nota de mediu local**: suita de teste era blocata local de o migratie
  preexistenta (`alter_export_subscriptions_frequency_enum`, sintaxa MySQL
  `MODIFY` incompatibila cu sqlite) - ocolita temporar doar pentru verificare
  (fisierul mutat si restaurat, neschimbat in git). A mai iesit la iveala si o
  problema de ordine intre migratiile Cashier publicate local (nefolosite in
  commit) si migratia de redenumire `subscriptions.user_id -> tenant_id` din
  Faza 5 billing-plans.md - rezolvata doar local, nu afecteaza productia (acolo
  Cashier a fost instalat inainte de a exista migratia de redenumire).
- **In afara scopului**: canal WhatsApp (Faza 2), actiuni reale de confirmare
  (statusul ramane un proxy calculat), recomandari bazate pe LLM extern.
- **Fix post-Faza-1 (2026-07-17)**: verificat live ca emailul/notificarea nu
  soseau desi setarile erau salvate corect - cauza reala: `config('app.timezone')`
  e `UTC` global in toata aplicatia (fara niciun concept de fus orar per-tenant),
  dar `send_time` e setat si inteles de utilizatori ca ora Romaniei. Comparatia
  din `SendDailySiteBriefingCommand` folosea `now()` (UTC), decalaj de 3 ore fata
  de asteptare. Reparat: comparatia (si "azi" din `DailyBriefingBuilder`) folosesc
  explicit `now('Europe/Bucharest')`.
- **Comenzi de suport pentru testare/diagnosticare** (nu fac parte din feature-ul
  in sine, adaugate dupa verificarea live): `briefing:seed-demo {email?}` (creeaza
  un proiect de test cu date programate azi pe toate cele 6 domenii + activeaza
  mementoul), `briefing:cleanup-demo {project}` (sterge complet - `forceDelete()`,
  proiectul foloseste `SoftDeletes` - un proiect de test si tot ce a creat,
  verifica numele inainte sa continue), `briefing:inspect` (afiseaza ora/fusul
  serverului + toate setarile salvate - inlocuieste `tinker --execute`, care nu
  functioneaza pe acest hosting fiindca `shell_exec()` e dezactivat).

## Faza 1b - v2.0: rezumat, risc, cronologie, export PDF, istoric (Facut, 2026-07-18)

Cerute de utilizator dupa verificarea live a Fazei 1 (7 imbunatatiri, confirmate
prin `AskUserQuestion`: cronologie in varianta usoara fara schema noua, istoric
cu jurnal complet/instantaneu, WhatsApp doar specificatie).

- `DailyBriefingBuilder::build()` primeste 3 chei noi: `risk_level`/`risk_label`
  (semafor verde/portocaliu/rosu, praguri simple pe numarul de blocaje: 0/1-2/3+),
  `summary` (propozitie scurta cu numarul de blocaje + primul blocaj ca prioritate),
  `timeline` (cronologie: task-urile au ora reala din `deadline`, restul
  domeniilor - care au doar DATA, fara ora - apar grupate "Toata ziua"; **decizie
  confirmata cu utilizatorul**: fara camp de ora editabil pe planificari, ca sa
  nu extindem schema modulelor existente).
- Tabel nou `project_daily_briefing_logs` (`App\Models\ProjectDailyBriefingLog`) -
  jurnal complet: la fiecare trimitere reala (`SendDailySiteBriefingCommand`),
  se salveaza un instantaneu (`snapshot` JSON = output-ul complet al `build()`)
  + risc/nr. blocaje/destinatari/canale - istoricul ramane corect chiar daca
  datele live se schimba ulterior. Fara retentie/curatare automata (tabel de
  audit, acelasi principiu ca `AccessAuditLog`/`ExportAudit`).
- `App\Support\DailyBriefingPdfExporter::buildSections()` (nou) - **refoloseste
  sablonul PDF generic deja existent** `exports/managerial-pdf.blade.php`
  (acelasi folosit de "Organizare Șantier"), fara view nou - doar transforma
  rezumat/cronologie/cele 6 sectiuni/blocaje/recomandari in formatul
  `{name, headings, rows}` deja asteptat de sablon.
- `DailyBriefing/Show.vue` - tab nou "Istoric" (lista intrarilor din jurnal,
  expandabil per intrare), banner nou de risc+rezumat pe tab-ul "Astazi", buton
  nou "Export PDF" in header. Sectiunile (echipe/subcontractori/materiale/
  utilaje/documente/task-uri/blocaje/recomandari/cronologie) extrase in
  componenta noua `resources/js/Components/DailyBriefingSections.vue`,
  refolosita atat pentru vizualizarea live cat si pentru fiecare intrare din
  istoric (acelasi shape de date).
- Email (`emails/daily-briefing.blade.php`) - banner de risc+rezumat, sectiune
  de cronologie, buton CTA reformulat ("Deschide agenda zilei in Modulia").
  Notificarea in-app (tot `OperationalReminderNotification`) foloseste acum
  risc+rezumat in loc de doar numarul de blocaje.
- Teste extinse: `DailyBriefingBuilderTest` (risc/rezumat/cronologie),
  `SendDailySiteBriefingCommandTest` (jurnalul se creeaza corect), plus
  `DailyBriefingHistoryTest` si `DailyBriefingPdfExportTest` noi.
- **In afara scopului**: implementarea reala a canalului WhatsApp, retentie
  automata a jurnalului, camp de ora editabil pe planificari.

## Faza 2 - Canal WhatsApp (Neinceput)

Necesita alegerea providerului (Twilio sau Meta Cloud API) si crearea unui cont
de catre utilizator, la fel ca la integrarea Stripe.

**Specificatie continut** (confirmata cu utilizatorul, de implementat cand se
alege providerul) - mesaj scurt, 3-4 linii, doar blocajele critice + link:
```
{emoji risc} Blocaje azi ({Nume proiect}):
– {blocaj 1}
– {blocaj 2}
– {blocaj 3}
Vezi detalii: {link catre daily-briefing.show}
```
Sursa datelor: aceleasi `briefing['risk_level']`/`briefing['blockers']` deja
calculate de `DailyBriefingBuilder` - la implementare, doar un nou "channel"
in `SendDailySiteBriefingCommand` (`channels.whatsapp`, deja prezent in schema
`project_daily_briefing_settings.channels` din Faza 1, neutilizat pana acum).
