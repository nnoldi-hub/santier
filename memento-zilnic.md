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

## Faza 2 - Canal WhatsApp (Neinceput)

Necesita alegerea providerului (Twilio sau Meta Cloud API) si crearea unui cont
de catre utilizator, la fel ca la integrarea Stripe.
