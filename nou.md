# NOU.MD - PLAN DE EXTINDERE PROFESIONALA (V1)

Scop: sa extindem produsul existent cu un flux complet de executie in santier, de la WBS pana la rapoarte de progres si control costuri pe etape.

## 1. Rezultat tinta
La finalul acestei extensii, fiecare proiect trebuie sa poata fi gestionat cap-coada astfel:

Proiect -> Etape (WBS) -> Responsabili (contractori/echipe) -> Utilaje -> Documente financiare -> Rapoarte zilnice -> Cost si progres cumulat.

## 2. Gap-ul curent (ce lipseste)
Cele 5 module obligatorii pentru maturitate operationala:

1. Etape de lucru (WBS nivel 1 + nivel 2)
2. Contractori/Subcontractori si echipe externe
3. Utilaje si alocare pe etape
4. Documente (contracte/facturi/devize/oferte)
5. Rapoarte de progres + taskuri pe etapa

## 3. Principii de implementare
1. Livrare incrementala pe faze (fiecare faza produce valoare utilizabila).
2. Fiecare modul nou are migration + model + policy + teste feature.
3. Nu rupem fluxurile existente (projects/tasks/defects/quotes/exports).
4. Datele noi intra in exporturile enterprise, altfel nu avem vizibilitate completa.

## 4. Plan pe faze (8 saptamani)

### Faza 1 (Sapt. 1-2): WBS + Contractori
Obiectiv: sa definim executia pe etape si cine raspunde de fiecare etapa.

Livrabile:
1. Modul Contractori (`contractors`) cu tipuri:
	- internal_team
	- subcontractor
	- pfa
	- equipment_supplier
	- materials_supplier
2. Extindere etape proiect (`project_stages`) cu:
	- contractor_id
	- status executie
	- progres
3. UI proiect:
	- tab Etape (lista + sub-etape)
	- alocare contractor pe etapa

Definition of Done:
1. Pot crea etape si sub-etape pentru un proiect.
2. Pot aloca contractor responsabil pe fiecare etapa.
3. Teste feature verzi pentru CRUD contractori + alocare pe etapa.

### Faza 2 (Sapt. 3-4): Utilaje pe etapa
Obiectiv: planificare reala de resurse tehnice.

Livrabile:
1. Modul Utilaje (`equipment`):
	- nume, tip, furnizor, cost_ora, disponibilitate
2. Pivot etapa-utilaj (`stage_equipment`):
	- stage_id, equipment_id, quantity, usage_start, usage_end
3. UI etapa:
	- rezervare utilaje
	- vizualizare cost estimat utilaje

Definition of Done:
1. Pot vedea ce utilaje sunt rezervate pe etapa.
2. Pot calcula cost estimat utilaje pentru interval.
3. Validare conflict de disponibilitate (minimum warning).

### Faza 3 (Sapt. 5-6): Documente financiare pe etapa
Obiectiv: trasabilitate financiara clara pe executie.

Livrabile:
1. Modul Documente (`documents`) cu tip:
	- contract
	- factura
	- deviz
	- oferta
2. Campuri cheie:
	- contractor_id, project_id, stage_id
	- amount, issued_at, payment_status
	- file_path
3. UI:
	- upload document
	- filtre pe tip/stare/contractor/etapa
	- sumar costuri per etapa

Definition of Done:
1. Pot vedea toate documentele legate de o etapa.
2. Pot filtra rapid facturile neplatite.
3. Sumar costuri pe etapa este corect in raport cu documentele.

### Faza 4 (Sapt. 7-8): Rapoarte + taskuri pe etapa
Obiectiv: control operational zilnic si comparatie plan vs real.

Livrabile:
1. Modul rapoarte etapa (`stage_reports`):
	- data, progres_pct, activitati, probleme
	- materiale_folosite, utilaje_folosite
	- imagini
2. Modul taskuri etapa (`stage_tasks`):
	- titlu, descriere, responsabil, deadline, status
3. Dashboard etapa:
	- progres cumulat
	- probleme deschise
	- cost estimat vs cost documentat

Definition of Done:
1. Pot face raport zilnic pe etapa cu progres si blocaje.
2. Pot assigna taskuri pe etapa si urmari statusul.
3. Exporturile includ date de etapa (raport + cost + progres).

## 5. Backlog tehnic (ordine recomandata)
1. Migrations + modele Eloquent pentru: contractors, equipment, stage_equipment, documents, stage_reports, stage_tasks.
2. Relatii in modele existente (Project, ProjectPhase, Team, Material).
3. Controllers + Requests + Policies pe fiecare modul.
4. Pagini Inertia/Vue pentru listare/create/edit/show.
5. Integrare in pagina proiectului si pagina etapei.
6. Exporturi enterprise: foi noi pentru Contractori, Utilaje, Documente, Rapoarte etapa.
7. Teste Feature per modul + regresii pe exporturi.

## 6. Impact in baza de date (schelet minim)
1. contractors
	- id, tenant_id, name, type, contact_name, phone, email, notes, created_at, updated_at
2. equipment
	- id, tenant_id, name, type, supplier_name, cost_per_hour, availability_status, created_at, updated_at
3. stage_equipment
	- id, stage_id, equipment_id, quantity, usage_start, usage_end, created_at, updated_at
4. documents
	- id, tenant_id, contractor_id, project_id, stage_id, type, amount, issued_at, payment_status, file_path, created_at, updated_at
5. stage_reports
	- id, stage_id, contractor_id, report_date, progress_pct, activities, issues, materials_used, equipment_used, images, created_by, created_at, updated_at
6. stage_tasks
	- id, stage_id, title, description, assignee_type, assignee_id, deadline, status, created_at, updated_at

## 7. KPI de produs pentru aceste module
1. >= 80% proiecte active cu WBS complet definit.
2. >= 70% etape active cu contractor alocat.
3. >= 60% etape active cu minim 1 raport saptamanal.
4. Diferenta plan vs real cost pe etapa vizibila in dashboard.

## 8. Riscuri si mitigare
1. Risc: crestere complexitate UI.
	- Mitigare: livrare pe tab-uri progresive in pagina proiect.
2. Risc: model de date prea rigid.
	- Mitigare: campuri JSON controlate unde variatia este mare (materials_used, equipment_used).
3. Risc: regresii pe exporturi.
	- Mitigare: teste dedicate pentru noile foi/sectiuni in pachet enterprise.

## 9. Criteriu de inchidere a initiativei
Initiativa este considerata inchisa doar daca:
1. Toate cele 5 module sunt in productie.
2. Exista teste feature verzi pentru fiecare modul.
3. Exportul enterprise include datele noi de executie.
4. Exista cel putin 1 proiect demo complet cu flux cap-coada.

## 10. Urmatorii 3 pasi imediati
1. Finalizam Faza 2 cu politici/permisiuni dedicate pentru modulul utilaje.
2. Decidem daca warning-ul de conflict devine blocare stricta (feature flag).
3. Pregatim kickoff Faza 3: schema + CRUD pentru documente financiare pe etapa.

## 11. Tracker progres pe etape (obligatoriu la final de etapa)

Regula de lucru:
1. La inchiderea fiecarei etape/faze, actualizam acest tracker in aceeasi zi.
2. Fiecare update trebuie sa raspunda la 3 intrebari: ce s-a livrat, ce a fost validat, ce ramane.
3. O etapa se marcheaza INCHIS doar daca are evidenta tehnica + validare (teste/build).

Legenda status:
- INCHIS = finalizat si validat.
- IN PROGRES = implementare activa.
- BLOCAT = exista blocker tehnic/functional.
- NEINCEPUT = nu a inceput implementarea.

### Status curent initiative (2026-07-01)

| Etapa | Status | Livrat | Validat | Ce ramane |
|---|---|---|---|---|
| Faza 1 - WBS + Contractori | INCHIS | CRUD contractori, alocare contractor pe etapa, sub-etape (`parent_id`), modul WBS dedicat, export WBS (CSV/XLSX/PDF/subscription) | `ContractorsTest` 3/3, `WbsIndexTest` 4/4, `EnterpriseExportsTest` 6/6, `npm run build` OK | N/A |
| Faza 2 - Utilaje pe etapa | INCHIS | Modul `equipment` (CRUD + filtre), pivot `stage_equipment`, rezervare utilaje din pagina proiect pe etapa, conflict handling configurabil (warning sau hard-block), export utilaje (CSV/XLSX/PDF/subscription), sumar cost utilaje in dashboard, policy dedicata `EquipmentPolicy` | `EquipmentManagementTest` 4/4, `WbsIndexTest` 4/4, `EnterpriseExportsTest` 7/7, `npm run build` OK | N/A |
| Faza 3 - Documente financiare pe etapa | INCHIS | Modul `documents` (migration + model + CRUD), upload/descarcare fisier, filtre pe tip/stare/proiect/etapa/contractor, sumar costuri pe etapa in index, integrare meniu enterprise, export documente (CSV/XLSX/PDF/subscription), policy dedicata `DocumentPolicy`, validare etapa-proiect, alerte restante + analytics financiare (paid/partial/unpaid, expunere, total pe contractor), KPI documente in dashboard | `DocumentsTest` 4/4, `DashboardFinancialInsightsTest` 1/1, `EnterpriseExportsTest` 8/8, `EquipmentManagementTest` 4/4, `npm run build` OK | N/A |
| Faza 4 - Rapoarte + taskuri pe etapa | INCHIS | Modul `stage_reports` (migration + model + CRUD + filtre), modul `stage_tasks` (migration + model + CRUD + filtre status/proiect/etapa + asignare user/team/contractor), integrare sidebar enterprise pentru noile module, relatii noi in `ProjectPhase`, dashboard etapa cu comparatie plan vs real (progres planificat vs progres raportat + cost documentat), export extins (`stage-reports`, `stage-tasks`) in CSV/XLSX/PDF/subscription | `StageReportsTest` 2/2, `StageTasksTest` 2/2, `DashboardFinancialInsightsTest` 1/1, `EnterpriseExportsTest` 10/10, `DocumentsTest` 4/4, `npm run build` OK | N/A |

## 12. Jurnal progres (cronologic)

### 2026-07-01 - Checkpoint extensie mini-calendar operational
- Etapa: extindere planning operational pe module active.
- Dovezi:
	- Mini-calendar integrat in pagina proiectului (`Projects/Show`) cu 6 fluxuri: etape, taskuri, utilaje, subcontractori, documente, calitate.
	- Selector interval pentru mini-calendar proiect: `Azi`, `7 zile`, `30 zile` (query param `calendar_window`, refresh Inertia partial doar pentru `todayCalendar`).
	- Mini-calendar utilaje integrat in `Equipment/Index` cu feed operational zilnic + link direct spre `equipment-calendar.index`.
	- Mini-calendar subcontractori integrat in `Contractors/Index` cu feed operational zilnic + link spre `team-calendar.index`.
	- Controller-ele `ProjectController`, `EquipmentController`, `ContractorController` livreaza acum payload dedicat `todayCalendar`.
- Validare:
	- `npm run build` -> passed.
	- `ProjectAiToolsTest` -> 3/3 passed (67 assertions).
	- `EquipmentCalendarTest`, `EquipmentManagementTest`, `ContractorsTest` -> 8/8 passed (46 assertions).
- Ce ramane:
	- Optional: extindere selector interval si in modulele `Equipment` si `Contractors` (momentan afisajul este pe agenda "azi").

### 2026-07-01 - Checkpoint unificare intervale mini-calendar (complet)
- Etapa: unificare UX si filtrare operationala pe toate modulele tinta.
- Dovezi:
	- `Equipment/Index` include selector interval `Azi / 7 zile / 30 zile` pentru mini-calendar.
	- `Contractors/Index` include selector interval `Azi / 7 zile / 30 zile` pentru mini-calendar.
	- `EquipmentController@index` si `ContractorController@index` filtreaza feed-ul mini-calendar pe `calendar_window` cu overlap real de interval.
	- Filtrele existente (`q`, `type`, `availability_status`) raman compatibile cu noul query param `calendar_window`.
- Validare:
	- `npm run build` -> passed.
	- `EquipmentCalendarTest`, `EquipmentManagementTest`, `ContractorsTest` -> 8/8 passed (46 assertions).
- Ce ramane:
	- N/A pentru obiectivul de integrare mini-calendar pe Proiect + Planificare + Utilaje + Subcontractori.

### 2026-07-01 - Checkpoint enterprise mini-calendar (risc + critical + navigare)
- Etapa: consolidare mini-calendar proiect la nivel enterprise.
- Dovezi:
	- Indicator AI nou in mini-calendar proiect: `Risc intarziere: X%`, calculat din 5 semnale operationale (etape risc, taskuri blocate, utilaje indisponibile, subcontractori supraincarcati, documente neplatite).
	- Evidentiere critica pe itemi (severitate high/medium/normal) cu cod de culoare operational in UI:
		- rosu pentru etape/taskuri cu risc,
		- portocaliu pentru documente sensibile,
		- albastru pentru utilaje,
		- mov pentru subcontractori,
		- verde pentru calitate.
	- Itemii din mini-calendar proiect sunt acum clickabili si functioneaza ca hub de navigare:
		- etapa -> ancora in pagina proiectului,
		- task -> edit task de etapa,
		- utilaj -> calendar utilaje filtrat pe interval/utilaj,
		- subcontractor -> profil contractor,
		- document -> edit document,
		- calitate -> edit verificare calitate.
- Validare:
	- `npm run build` -> passed.
	- `ProjectAiToolsTest`, `EquipmentCalendarTest`, `EquipmentManagementTest`, `ContractorsTest` -> 11/11 passed (113 assertions).
- Ce ramane:
	- Optional: tuning de formula AI de risc pe baza datelor istorice reale din productie.

### 2026-07-01 - Checkpoint dashboard calendar AI (actualizare completa)
- Etapa: aliniere Dashboard cu noul standard mini-calendar enterprise.
- Dovezi:
	- Calendarul din Dashboard include acum card AI "Risc intarziere azi: X%" cu nivel `low/medium/high`.
	- Evenimentele din cele 6 categorii au severitate explicita (`criticality`) si stiluri unificate pe culori.
	- Toate itemele din calendarul Dashboard sunt clickabile (navigare directa spre paginile relevante: proiect/etapa, task, calendar utilaje filtrat, contractor, document, verificare calitate).
	- Payload-ul `todayCalendar` din `routes/web.php` livreaza acum metadata extinsa: `url`, `criticality`, `risk` agregat.
- Validare:
	- `npm run build` -> passed.
	- `DashboardFinancialInsightsTest` -> 1/1 passed (26 assertions).
- Ce ramane:
	- Optional: expunerea transparentei scorului AI (tooltip cu ponderi) direct in cardul de risc.

### 2026-07-01 - Checkpoint dashboard calendar AI predictiv (v2)
- Etapa: extindere asistent executie cu predictii, filtre si semnal de incarcare.
- Dovezi:
	- Selector avansat `Azi / 7 zile / 30 zile` activ in Dashboard Calendar, cu refresh Inertia pe `todayCalendar`.
	- Filtre pe categorii active: `Etape`, `Taskuri`, `Subcontractori`, `Utilaje`, `Documente`, `Calitate`.
	- Indicator AI predictiv extins cu 3 fluxuri explicite:
		- risc intarziere pe etape,
		- risc depasire buget,
		- risc subcontractor (proiecte paralele).
	- Toate predictiile si itemii calendarului raman clickabile (hub operational de navigare).
	- Indicator de incarcare pe zi introdus (`Zi lejera`, `Zi normala`, `Zi critica`) cu progress bar bazat pe numarul de evenimente.
- Validare:
	- `npm run build` -> passed.
	- `DashboardFinancialInsightsTest` + `ProjectAiToolsTest` -> 4/4 passed (93 assertions).

### 2026-07-09 - Checkpoint exporturi enterprise si audit operational
- Etapa: extindere exporturi enterprise si clarificare trasabilitate operativa.
- Livrat:
	- Butonul de stergere din resource orders a fost stabilizat cu UX de loading, refresh explicit si handling de eroare.
	- Istoric audit pentru resource orders: cine a creat, confirmat, atasat documente si sters documente/comenzi.
	- Pagina exporturi a primit filtre rapide, preview si template-uri one-click.
	- Lista de exporturi a fost reorganizata in tab-uri pe domenii pentru navigare mai clara.
	- A fost adaugat raportul comparativ `Materiale & Avize comparative` cu date despre comandat, livrat, receptionat, consumat, returnat si diferente din documente.
	- Exporturile enterprise, preview-ul si abonarea pe email sunt acoperite de teste feature si build validat.
- Polish vizual recent:
	- Preview-ul raportului comparativ a fost refacut in stil dashboard, cu header accent, KPI cards si mostre lizibile pe randuri.
- Validare:
	- `tests/Feature/ResourceOrdersTest.php` -> passed.
	- `tests/Unit/ExportFilterTest.php` -> passed.
	- `tests/Feature/EnterpriseExportsTest.php` -> passed.
	- `npm run build` -> passed.
- Ce ramane:
	- Tab-uri dedicate pentru rapoarte pe domenii avansate, cu subfiltre per categorie.
	- Extindere pentru rapoarte comparatives suplimentare pe costuri, utilaje si etape.
	- Optimizare vizuala suplimentara pentru layout-ul exporturilor daca apar noi blocuri functionale.
- Ce ramane:
	- Optional: calibrare scoruri predictive cu istoric real si feedback din productie.

### 2026-07-01 - Checkpoint explicabilitate risc (tooltip factori)
- Etapa: transparenta AI pentru predictiile din Dashboard Calendar.
- Dovezi:
	- Predictiile `risc intarziere`, `risc depasire buget` si `risc subcontractor` includ acum breakdown pe factori (`label`, `impact`, `detail`) in payload.
	- In UI, fiecare rand predictiv afiseaza indicator `detalii` cu tooltip pe hover care explica formula si contributiile.
	- Operatorul poate vedea direct de ce un risc este 18%/22% fara investigatie suplimentara in alte module.
- Validare:
	- `npm run build` -> passed.
	- `DashboardFinancialInsightsTest` -> 1/1 passed (26 assertions).
- Ce ramane:
	- Optional: transformarea tooltip-ului in popover rich (cu iconite si punctaj colorat).

### 2026-07-01 - Checkpoint explicabilitate risc (popover rich)
- Etapa: UX avansat pentru explicatiile predictive din Dashboard.
- Dovezi:
	- `detalii` nu mai este doar tooltip text; fiecare predicție deschide popover vizual cu lista de factori.
	- Popover-ul afiseaza pe rand `label`, `impact`, `detail` pentru fiecare factor.
	- Impactul factorilor este colorat automat (ex: `+` rosu, `%` portocaliu, `x` mov) pentru lectura rapida.
	- Navigarea pe item-ul principal ramane activa; popover-ul este controlat separat prin butonul `detalii`.
- Validare:
	- `npm run build` -> passed.
	- `DashboardFinancialInsightsTest` -> 1/1 passed (26 assertions).
- Ce ramane:
	- Optional: inchidere popover la click in afara + animatie fina la deschidere.

### 2026-07-01 - Checkpoint
- Etapa inchisa: Faza 1 (WBS + Contractori).
- Dovezi:
	- Implementare completa WBS ierarhic (parinte/copil), contractor pe etapa, quick update si guard anti-ciclu.
	- Export enterprise extins cu tipul `wbs` in CSV/XLSX/PDF + abonari programate.
	- Sidebar modular enterprise actualizat pentru acces rapid la module.
- Validare:
	- `EnterpriseExportsTest` -> 6/6 passed.
	- `WbsIndexTest` -> 4/4 passed.
	- `npm run build` -> passed.
- Urmatorul focus: Faza 2 (Utilaje pe etapa).

### 2026-07-01 - Checkpoint Faza 2 (increment 1)
- Etapa: Faza 2 in progres.
- Dovezi:
	- Schema noua livrata: `equipment` si `stage_equipment`.
	- CRUD utilaje complet (listare/create/edit/delete) + filtre dupa tip/disponibilitate.
	- Integrare in proiect: rezervare utilaje pe fiecare etapa, cu interval si cantitate.
	- Warning de suprapunere interval pentru acelasi utilaj (rezervarea se salveaza cu atentionare).
	- Estimare cost interval afisata in UI pe baza cost/ora * cantitate * interval.
- Validare:
	- `EquipmentManagementTest` -> 3/3 passed.
	- `WbsIndexTest` -> 4/4 passed.
	- `EnterpriseExportsTest` -> 6/6 passed.
	- `npm run build` -> passed.
- Ce ramane in Faza 2:
	- Exporturi dedicate utilaje.
	- KPI/sumar cost utilaje in raportare.
	- Hard-rule optional pentru blocare conflict (in loc de warning).

### 2026-07-01 - Checkpoint Faza 2 (increment 2)
- Etapa: Faza 2 in progres.
- Dovezi:
	- Export utilaje introdus complet in pipeline (`equipment`) pentru CSV/XLSX/PDF si abonari programate.
	- Dashboard extins cu KPI nou: cost estimat utilaje pe baza rezervarilor.
	- UI Exporturi actualizat cu card si optiune de subscription pentru utilaje.
- Validare:
	- `EnterpriseExportsTest` -> 7/7 passed.
	- `EquipmentManagementTest` -> 3/3 passed.
	- `WbsIndexTest` -> 4/4 passed.
	- `npm run build` -> passed.
- Ce ramane in Faza 2:
	- Politici/permisiuni dedicate pentru utilaje.
	- Hard-rule optional pentru blocare conflict interval.

### 2026-07-01 - Checkpoint Faza 2 (inchidere)
- Etapa inchisa: Faza 2 (Utilaje pe etapa).
- Dovezi:
	- Policy dedicata pentru modulul utilaje (`EquipmentPolicy`) + autorizare resource in controller.
	- Config nou `equipment.strict_conflict_block` pentru comutare warning vs blocare stricta la suprapuneri.
	- Teste pentru ambele moduri de conflict (warning si strict block) in suita de utilaje.
- Validare:
	- `EquipmentManagementTest` -> 4/4 passed.
	- `EnterpriseExportsTest` -> 7/7 passed.
	- `WbsIndexTest` -> 4/4 passed.
	- `npm run build` -> passed.
- Urmatorul focus: Faza 3 (Documente financiare pe etapa).

### 2026-07-01 - Checkpoint Faza 3 (increment 1)
- Etapa: Faza 3 in progres.
- Dovezi:
	- Schema `documents` livrata cu legaturi spre proiect, etapa, contractor + metadate de fisier.
	- CRUD documente implementat complet (listare/create/edit/stergere).
	- Upload + download fisier functional din storage local privat.
	- Filtre functionale pe tip, status plata, proiect, etapa, contractor.
	- Sumar costuri pe etapa afisat in pagina de documente.
- Validare:
	- `DocumentsTest` -> 3/3 passed.
	- `EnterpriseExportsTest` -> 7/7 passed.
	- `EquipmentManagementTest` -> 4/4 passed.
	- `npm run build` -> passed.
- Ce ramane in Faza 3:
	- Exporturi dedicate documente in pipeline enterprise.
	- Politici/permisiuni pentru documente.
	- Reguli suplimentare pentru consistenta financiara (ex: etapa apartine proiectului selectat).

### 2026-07-01 - Checkpoint Faza 3 (increment 2)
- Etapa: Faza 3 in progres.
- Dovezi:
	- Export `documents` introdus in pipeline enterprise pentru CSV/XLSX/PDF si abonari programate.
	- UI Exporturi actualizat cu card dedicat pentru documente + optiune `documents` in subscription.
	- Policy dedicata pentru documente (`DocumentPolicy`) + autorizare resource in controller.
	- Validare avansata in request: etapa selectata trebuie sa apartina proiectului selectat.
- Validare:
	- `DocumentsTest` -> 4/4 passed.
	- `EnterpriseExportsTest` -> 8/8 passed.
	- `EquipmentManagementTest` -> 4/4 passed.
	- `npm run build` -> passed.
- Ce ramane in Faza 3:
	- Fine-tuning reguli financiare (ex: praguri/alerte pentru restante).
	- Extindere analytics pe documente (paid/unpaid trend, total pe contractor).

### 2026-07-01 - Checkpoint Faza 3 (increment 3 + inchidere)
- Etapa inchisa: Faza 3 (Documente financiare pe etapa).
- Dovezi:
	- Alerte financiare pentru restante adaugate in index documente (facturi `unpaid` mai vechi de 30 zile).
	- Analytics financiare extinse in documente: distributie paid/partial/unpaid + expunere financiara (`unpaid` + `partial`).
	- Tabel nou de totaluri pe contractor in modulul documente.
	- Dashboard extins cu KPI documente: numar neplatite/partial, suma expunere, restante >30 zile.
	- Compatibilitate SQL imbunatatita in dashboard (ordonare prioritati cu `CASE`, compatibila SQLite pentru teste).
- Validare:
	- `DocumentsTest` -> 4/4 passed.
	- `DashboardFinancialInsightsTest` -> 1/1 passed.
	- `EnterpriseExportsTest` -> 8/8 passed.
	- `npm run build` -> passed.
- Ce ramane:
	- Kickoff Faza 4: modelare `stage_reports` si `stage_tasks`.

## 13. Format obligatoriu de update dupa fiecare etapa

### 2026-07-01 - Checkpoint Faza 4 (increment 1)
- Etapa: Faza 4 in progres.
- Dovezi:
	- Schema noua livrata: `stage_reports` si `stage_tasks`.
	- Modul `stage_reports` implementat complet (listare/create/edit/stergere) cu filtre pe proiect/etapa/contractor si raportare progres %.
	- Modul `stage_tasks` implementat complet (listare/create/edit/stergere) cu status operational, deadline si asignare pe tip responsabil (user/team/contractor).
	- Sidebar modular enterprise conectat la noile module (`stage-reports`, `stage-tasks`).
	- Relatii in `ProjectPhase` pentru rapoarte si taskuri de etapa.
- Validare:
	- `StageReportsTest` -> 2/2 passed.
	- `StageTasksTest` -> 2/2 passed.
	- `DocumentsTest` -> 4/4 passed.
	- `npm run build` -> passed.
- Ce ramane in Faza 4:
	- Dashboard etapa cu comparatie plan vs real (progres + cost documentat).
	- Export extins cu foi/sectiuni pentru `stage_reports` si `stage_tasks`.

### 2026-07-01 - Checkpoint Faza 4 (increment 2 + inchidere)
- Etapa inchisa: Faza 4 (Rapoarte + taskuri pe etapa).
- Dovezi:
	- Dashboard extins cu widget "Plan vs Real pe etape" (planificat vs raportat, delta, cost documentat, data ultim raport).
	- KPI nou in dashboard: taskuri de etapa deschise (`todo` + `in_progress` + `blocked`).
	- Exporturi enterprise extinse cu tipuri noi: `stage-reports` si `stage-tasks` (CSV + suport in workbook/PDF/subscription).
	- UI Exporturi actualizat cu carduri dedicate pentru noile tipuri si optiuni in formularul de abonare.
	- Rute noi de export adaugate pentru noile module: `exports.stage-reports`, `exports.stage-tasks`.
- Validare:
	- `DashboardFinancialInsightsTest` -> 1/1 passed.
	- `EnterpriseExportsTest` -> 10/10 passed.
	- `StageReportsTest` -> 2/2 passed.
	- `StageTasksTest` -> 2/2 passed.
	- `DocumentsTest` -> 4/4 passed.
	- `npm run build` -> passed.
- Ce ramane:
	- N/A (Faza 4 inchisa).

### 2026-07-01 - Checkpoint Inchidere Initiativa (tehnic)
- Status: INITIATIVA INCHISA TEHNIC (pre-productie).
- Dovezi:
	- Toate fazele sunt inchise (Faza 1, Faza 2, Faza 3, Faza 4).
	- Fluxul cap-coada este implementat: Proiect -> WBS -> Utilaje -> Documente financiare -> Rapoarte etapa -> Taskuri etapa -> Exporturi enterprise.
	- Exporturile enterprise acopera si noile module de executie (`stage-reports`, `stage-tasks`) in CSV/XLSX/PDF/subscription.
	- Dashboard include indicatori operationali si financiari extinsi (documente restante, taskuri etapa deschise, plan vs real pe etape).
- Validare smoke (final):
	- `ContractorsTest` -> 3/3 passed.
	- `WbsIndexTest` -> 4/4 passed.
	- `EquipmentManagementTest` -> 4/4 passed.
	- `DocumentsTest` -> 4/4 passed.
	- `StageReportsTest` -> 2/2 passed.
	- `StageTasksTest` -> 2/2 passed.
	- `DashboardFinancialInsightsTest` -> 1/1 passed.
	- `EnterpriseExportsTest` -> 10/10 passed.
	- `npm run build` -> passed.
- Ce ramane pentru GO-LIVE:
	- Validare operationala in mediu de productie/staging si date reale.
	- Stabilizare rulare dev frontend (`npm run dev`) pe port liber sau configuratie alternativa de port.

### 2026-07-01 - Checkpoint Extensie (modul Verificari)
- Etapa: backlog extins post-inchidere (modul nou activat).
- Dovezi:
	- Modul `quality_checks` livrat complet: migration + model + request + policy + controller + UI (index/create/edit).
	- Filtre functionale pe status/tip/proiect si update rapid de status in listare.
	- Validare consistenta: etapa selectata trebuie sa apartina proiectului selectat.
	- Integrare sidebar enterprise: sectiunea Calitate -> Verificari este activa (nu mai este `Soon`).
- Validare:
	- `QualityChecksTest` -> 3/3 passed.
	- `DocumentsTest` -> 4/4 passed.
	- `npm run build` -> passed.
	- `artisan migrate` -> tabela `quality_checks` aplicata local.
- Ce ramane in backlog "Soon":
	- Calendar echipe.
	- Calendar utilaje.
	- Facturi materiale.
	- Situatii de lucrari.
	- Rapoarte calitate.
	- Procese verbale.
	- Documente subcontractori.
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Calendar echipe)
- Etapa: backlog extins post-inchidere (modul nou activat).
- Dovezi:
	- Modul `team-calendar` livrat ca agenda operativa peste `PhaseTeamAssignment`.
	- Calendarul agrega alocarile pe interval, echipe si etape/proiecte, cu sumar operational.
	- Filtre functionale pe interval de date si pe echipa.
	- Integrare sidebar enterprise: sectiunea Planificare -> Calendar echipe este activa (nu mai este `Soon`).
- Validare:
	- `TeamCalendarTest` -> 1/1 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Calendar utilaje.
	- Facturi materiale.
	- Situatii de lucrari.
	- Rapoarte calitate.
	- Procese verbale.
	- Documente subcontractori.
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Calendar utilaje)
- Etapa: backlog extins post-inchidere (modul nou activat).
- Dovezi:
	- Modul `equipment-calendar` livrat ca agenda operativa peste `StageEquipment`.
	- Calendarul agregă rezervările de utilaje pe interval, proiect și etapă, cu sumar de utilizare și cost estimat.
	- Filtre funcționale pe interval de date și pe utilaj.
	- Integrare sidebar enterprise: sectiunea Planificare -> Calendar utilaje este activa (nu mai este `Soon`).
- Validare:
	- `EquipmentCalendarTest` -> 1/1 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Facturi materiale.
	- Situatii de lucrari.
	- Rapoarte calitate.
	- Procese verbale.
	- Documente subcontractori.
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Situatii de lucrari)
- Etapa: backlog extins post-inchidere (modul activat prin alias de navigatie).
- Dovezi:
	- Intrarea `Situatii de lucrari` este activa si duce la lista existenta de rapoarte de etapa.
	- Ruta dedicata `situatii-lucrari.index` este disponibila ca alias peste `StageReportController@index`.
	- Modulul reutilizeaza deja filtrele, create/edit si stergere din `StageReports`.
- Validare:
	- `StageReportsTest` -> 2/2 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Rapoarte calitate.
	- Procese verbale.
	- Documente subcontractori.
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Rapoarte calitate)
- Etapa: backlog extins post-inchidere (modul activat prin alias de navigatie).
- Dovezi:
	- Intrarea `Rapoarte calitate` este activa si duce la lista existenta de verificari.
	- Ruta dedicata `rapoarte-calitate.index` este disponibila ca alias peste `QualityCheckController@index`.
	- Modulul reutilizeaza deja filtrele, create/edit si update status din `QualityChecks`.
- Validare:
	- `QualityReportsAliasTest` -> 1/1 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Procese verbale.
	- Documente subcontractori.
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Procese verbale)
- Etapa: backlog extins post-inchidere (modul activat prin alias de navigatie).
- Dovezi:
	- Intrarea `Procese verbale` este activa si duce la lista existenta de documente.
	- Ruta dedicata `procese-verbale.index` este disponibila ca alias peste `DocumentController@index`.
	- Modulul reutilizeaza deja filtrele, create/edit, incarcarea fisierelor si descarcarea documentelor din `Documents`.
- Validare:
	- `ProceseVerbaleAliasTest` -> 1/1 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Documente subcontractori.
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Documente subcontractori)
- Etapa: backlog extins post-inchidere (modul activat prin alias de navigatie).
- Dovezi:
	- Intrarea `Documente subcontractori` este activa si duce la lista de contractori filtrata pe subcontractori.
	- Ruta dedicata `documente-subcontractori.index` este disponibila ca alias peste `ContractorController@subcontractors`.
	- Modulul reutilizeaza deja UI-ul, filtrele si actiunile din `Contractors`, cu prefiltrare pe tipul `subcontractor`.
- Validare:
	- `DocumenteSubcontractoriAliasTest` -> 1/1 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Facturi materiale)
- Etapa: backlog extins post-inchidere (modul nou activat).
- Dovezi:
	- Modul `material_invoices` livrat complet: migration + model + request + policy + controller + UI (index/create/edit).
	- Filtre functionale pe status plata/proiect/cautare, plus sumar financiar in listare (expunere neplatita, total platit).
	- Validare consistenta: etapa selectata trebuie sa apartina proiectului selectat.
	- Integrare sidebar enterprise: sectiunea Financiar -> Facturi materiale este activa (nu mai este `Soon`).
- Validare:
	- `artisan migrate` -> tabela `material_invoices` aplicata local.
	- `MaterialInvoicesTest` -> 3/3 passed.
	- `QualityChecksTest` -> 3/3 passed.
	- `EnterpriseExportsTest` -> 10/10 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Calendar echipe.
	- Calendar utilaje.
	- Situatii de lucrari.
	- Rapoarte calitate.
	- Procese verbale.
	- Documente subcontractori.
	- Cost tracking.
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Cost tracking)
- Etapa: backlog extins post-inchidere (modul nou activat).
- Dovezi:
	- Modul `cost-tracking` livrat ca sumar financiar peste dataset-ul existent de costuri.
	- Tracking-ul arata buget, total oferte, total acceptat, si top proiecte dupa abatere.
	- Integrare sidebar enterprise: sectiunea Financiar -> Cost tracking este activa (nu mai este `Soon`).
- Validare:
	- `CostTrackingTest` -> 1/1 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Progres etape (mod raportare dedicat).

### 2026-07-01 - Checkpoint Extensie (modul Progres etape)
- Etapa: backlog extins post-inchidere (modul nou activat).
- Dovezi:
	- Modul `stage-progress` livrat ca dashboard dedicat pentru progresul pe etape.
	- Dashboard-ul arata progres mediu, etape finalizate, etape incepute si lista sortata a etapelelor.
	- Filtre functionale pe proiect, status, contractor si cautare text.
	- Integrare sidebar enterprise: sectiunea Raportare -> Progres etape este activa (nu mai este `Soon`).
- Validare:
	- `StageProgressTest` -> 1/1 passed.
	- `npm run build` -> passed.
- Ce ramane in backlog "Soon":
	- Niciun modul nou clar ramas; backlog-ul Soon este consumat.

La fiecare inchidere de etapa adaugam in acest fisier un bloc nou in sectiunea "Jurnal progres":

1. Data + nume etapa.
2. Ce s-a livrat (maxim 5 bullets concrete).
3. Ce s-a validat (teste/build/comenzi).
4. Ce ramane (urmatorul increment clar, executabil).

## 13. Proiect demo pentru evaluare aplicatie

Scop: sa avem un proiect unic, realist, prin care putem parcurge fluxul principal al aplicatiei si nota rapid plusurile si minusurile.

Comanda de pregatire:
1. `php artisan db:seed --class=PublicDemoSeeder`

Cont demo:
1. Email: `demo@santier.local`
2. Parola: `Demo1234!`

Proiectul principal de evaluare:
1. `Renovare Office Park - Corp A`

Ce contine proiectul demo:
1. Client + 2 proiecte active pentru vizualizare portofoliu.
2. WBS ierarhic pe Corp A: etapa parinte + sub-etape de instalatii si finisaje.
3. Contractori alocati pe etape, echipa interna alocata si capacitate partial ocupata.
4. Utilaje rezervate pe etape, cu cost/ora si intervale distincte.
5. Documente financiare mixte: contract, factura partial platita, deviz, oferta.
6. Rapoarte de etapa cu progres, activitati, probleme, materiale si utilaje folosite.
7. Taskuri generale + taskuri pe etapa cu 3 tipuri de responsabil: user, echipa, contractor.
8. Quality checks, defect deschis, materiale si facturi materiale pentru urmarire financiara.
9. Date suficiente pentru dashboard, cost tracking, progres etape si exporturi enterprise.

Scenariu minim de evaluare produs:
1. Dashboard: verificam daca indicatorii explica bine situatia proiectului fara sa intram imediat in detalii.
2. Proiecte -> WBS: verificam daca structura pe etape si progresul sunt usor de inteles.
3. Planificare -> Calendar echipe / Calendar utilaje: verificam daca resursele sunt vizibile si daca apar zone de conflict sau suprapunere.
4. Financiar -> Documente / Facturi materiale / Cost tracking: verificam daca putem urmari rapid expunerea si diferentele vs buget.
5. Calitate -> Verificari / Rapoarte calitate: verificam daca blocajele si controalele sunt localizabile rapid.
6. Raportare -> Progres etape / Exporturi: verificam daca managementul poate extrage statusul fara interpretare manuala.

Template de evaluare plusuri / minusuri:
1. Plus: ce se intelege instant fara training.
2. Minus: unde trebuie prea multe click-uri sau context ascuns.
3. Plus: ce raport sau dashboard ajuta direct decizia PM/owner.
4. Minus: unde datele sunt corecte, dar prezentarea nu sustine prioritatea.
5. Minus critic: orice pas unde un utilizator nou nu ar sti clar ce sa faca mai departe.

### 2026-07-01 - Checkpoint Hardening Demo (scenariu evaluare)
- Etapa: consolidare experienta demo pentru review de produs.
- Dovezi:
	- Proiectul demo `Renovare Office Park - Corp A` este seed-uit cap-coada prin `PublicDemoSeeder` si poate fi refolosit repetabil.
	- Ecranele cheie de evaluare pentru utilizatorul demo sunt izolate de datele vechi din tenant: dashboard, WBS, cost tracking, exporturi, progres etape, calendare, documente si facturi materiale.
	- UX-ul a fost clarificat pe exporturi, dashboard si sidebar pentru a sustine mai bine o evaluare de produs.
	- Aplicatia afiseaza explicit `Mod demo`, astfel incat contextul filtrat este vizibil pentru evaluator.
- Validare:
	- `DemoUserScopeTest` -> 1/1 passed.
	- `npm run build` -> passed.
	- `php artisan db:seed --class=PublicDemoSeeder` -> passed, inclusiv rerulare idempotenta.
- Ce ramane:
	- Putem trece fie la un nou modul, fie la polish UX suplimentar pe baza concluziilor din testul de produs.

### 2026-07-01 - Checkpoint UX Polish (Documente financiare + Progres etape)
- Etapa: hardening UX pentru ecranele de evaluare management.
- Dovezi:
	- Pagina `Documente financiare` include acum blocul `Prioritate acum` cu alerte actionabile (restante >30 zile, neplatite, expunere maxima pe contractor/etapa).
	- Pagina `Progres etape` include indicatori de risc operational (`In lucru`, etape blocate/stagnante) si un bloc `Prioritate acum` cu etapa prioritara si actiuni recomandate.
	- Schimbarile folosesc datele deja existente in backend (fara migrari/schimbari schema), orientate strict pe claritate decizionala.
- Validare:
	- `npm run build` -> passed (dupa fiecare increment).
	- Verificare erori pe componentele afectate -> fara erori (`Documents/Index.vue`, `StageProgress/Index.vue`).
- Ce ramane:
	- Optional: extindere aceluiasi model de prioritizare pe alte ecrane operationale (`Calendar echipe`, `Calendar utilaje`).

### 2026-07-01 - Checkpoint UX Polish (Facturi materiale)
- Etapa: aliniere consistenta UX in zona financiara.
- Dovezi:
	- Pagina `Facturi materiale` include acum blocul `Prioritate acum` cu factura prioritara si recomandare de actiune.
	- Sunt afisate alerte actionabile pentru restante dupa scadenta, facturi deschise (`unpaid` + `partial`) si expunerea maxima pe factura.
	- Logica de prioritizare este calculata din datele deja disponibile in pagina (fara schimbari backend).
- Validare:
	- `npm run build` -> passed.
	- Verificare erori pe componenta afectata -> fara erori (`MaterialInvoices/Index.vue`).
- Ce ramane:
	- Optional: test de utilizabilitate rapid pe fluxul financiar complet (`Documente`, `Facturi materiale`, `Cost tracking`).

### 2026-07-01 - Checkpoint Validare Flux Financiar (smoke practic)
- Etapa: verificare cap-coada dupa polish UX.
- Dovezi:
	- Flux validat in browser pe contul demo: `Dashboard -> Documente financiare -> Facturi materiale -> Cost tracking`.
	- Blocurile `Prioritate acum` sunt vizibile si populate in paginile `Documente financiare` si `Facturi materiale`.
	- Navigarea financiara din sidebar functioneaza fara elemente `Soon` in acest flux.
- Validare:
	- Backend health check (`http://127.0.0.1:8080`) -> HTTP 200.
	- `artisan test` pe suita financiara -> `9 passed` (`DocumentsTest`, `MaterialInvoicesTest`, `CostTrackingTest`, `DashboardFinancialInsightsTest`).
	- `npm run build` -> passed.
- Ce ramane:
	- Optional: adaugare test E2E Playwright pentru interactiuni avansate (filtre combinate + navigare rapida).

### 2026-07-01 - Checkpoint Regresie Automata (flux financiar)
- Etapa: consolidare testare automata dupa validarea practica.
- Dovezi:
	- Test nou adaugat: `tests/Feature/FinancialFlowSmokeTest.php`.
	- Testul valideaza cap-coada ecranele financiare principale in context autentic de date: `Documents`, `MaterialInvoices`, `CostTracking`.
	- Asigura regresia pe datele-cheie Inertia care alimenteaza blocurile de prioritizare si sumarul financiar.
- Validare:
	- `artisan test tests/Feature/FinancialFlowSmokeTest.php` -> passed (1/1).
	- Bundle financiar complet -> passed (10/10): `DocumentsTest`, `MaterialInvoicesTest`, `CostTrackingTest`, `DashboardFinancialInsightsTest`, `FinancialFlowSmokeTest`.
- Ce ramane:
	- N/A (integrarea in CI este livrata).

### 2026-07-01 - Checkpoint CI (regresie financiara)
- Etapa: operationalizare validare automata in pipeline GitHub.
- Dovezi:
	- Workflow nou adaugat: `.github/workflows/financial-regression.yml`.
	- Pipeline-ul ruleaza pe `pull_request`, `push` pe `main/master` si `workflow_dispatch`.
	- Job-ul `Financial Test Bundle` pregateste mediul Laravel si ruleaza pachetul financiar complet de regresie.
- Validare:
	- Configuratia workflow este prezenta in repository si acopera explicit cele 5 teste financiare relevante.
	- Bundle-ul local corespondent a fost deja validat verde (`10/10`).
- Ce ramane:
	- Optional: adaugare branch protection rule care cere acest workflow ca status check obligatoriu.

### 2026-07-01 - Checkpoint Launch Readiness (operationalizare)
- Etapa: pregatire executabila pentru saptamana de lansare.
- Dovezi:
	- `GO_LIVE_DAY.md` extins cu sectiunea `Launch Week Checklist (executabil)`.
	- Checklist-ul include task-uri clare cu `Owner`, `Deadline`, `Status` si evidenta minima.
	- Reguli de cadenta adaugate pentru update de 2 ori/zi si criteriu explicit de Go-Live.
- Validare:
	- Structura permite urmarire operationala zilnica fara interpretari suplimentare.
- Ce ramane:
	- Marcare progres real pe LW-01..LW-14 in functie de executia echipei.

### 2026-07-01 - Checkpoint AI Tools (MVP Card 1 - Factura prin poza)
- Etapa: introducere modul AI in pagina proiectului + flow functional minim.
- Dovezi:
	- Pagina proiectului include modul nou `AI Tools` cu 3 carduri, dintre care Card 1 este functional (`Factura prin poza`).
	- Flow Card 1 implementat cap-coada: upload fisier -> extractie draft AI -> revizuire manuala -> confirmare -> creare `Document` + `Contractor` (furnizor).
	- Endpoint-uri noi pe proiect pentru AI invoice flow:
		- `projects.ai.invoice.extract`
		- `projects.ai.invoice.commit`
	- Test dedicat adaugat: `ProjectAiToolsTest`.
- Validare:
	- `artisan test tests/Feature/ProjectAiToolsTest.php` -> passed (1/1).
	- `npm run build` -> passed.
	- `get_errors` pe fisierele noi/modificate -> fara erori.
- Ce ramane:
	- Sprint 2: Card 3 (Alerta depasire buget) cu recomandari automate pe etapa.
	- Sprint 3: Card 2 (Deviz automat) pe baza normelor tehnice.

### 2026-07-01 - Checkpoint AI Tools (MVP Card 3 - Alerta depasire buget)
- Etapa: extindere modul AI cu analiza financiara pe etapa/proiect.
- Dovezi:
	- Card 3 (`Alerta depasire buget`) activat in modulul `AI Tools` din pagina proiectului.
	- Flow functional nou: utilizatorul introduce achizitia + etapa, AI calculeaza depasirea pe etapa, impactul pe profit si ofera recomandare.
	- Endpoint nou adaugat: `projects.ai.budget-alert`.
	- Rezultatul afiseaza: depasire suma/procent, cost estimat post-achizitie, impact pe profit, recomandare contextuala.
	- Test dedicat extins in `ProjectAiToolsTest` pentru scenariul de depasire.
- Validare:
	- `artisan test tests/Feature/ProjectAiToolsTest.php` -> passed (2/2).
	- `npm run build` -> passed.
	- `get_errors` pe fisierele afectate -> fara erori.
- Ce ramane:
	- Sprint 3: Card 2 (Deviz automat din dimensiuni) cu output materiale/manopera/utilaje + propunere WBS.

### 2026-07-01 - Checkpoint AI Tools (MVP Card 2 - Deviz automat din dimensiuni)
- Etapa: finalizare modul AI cu flow de estimare tehnico-financiara.
- Dovezi:
	- Card 2 (`Deviz automat din dimensiuni`) este activ in `AI Tools` pe pagina proiectului, cu modal complet pentru input (`tip lucrare`, `tip dimensiune`, `valoare`, `complexitate`).
	- Endpoint-uri noi pentru Card 2:
		- `projects.ai.estimate.generate` (genereaza materiale/manopera/utilaje/totale + etape WBS propuse)
		- `projects.ai.estimate.commit` (salveaza oferta draft in `quotes` + creeaza etape lipsa in `project_phases`)
	- UI afiseaza sumar costuri, lista materiale estimate, etape WBS propuse si permite commit direct in proiect.
	- Test dedicat nou in `ProjectAiToolsTest` pentru flow cap-coada generate -> commit.
- Validare:
	- `artisan test tests/Feature/ProjectAiToolsTest.php` -> passed (3/3).
	- `npm run build` -> passed.
	- `get_errors` pe fisierele afectate (`ProjectAiToolsController`, `Projects/Show.vue`, `routes/web.php`, `ProjectAiToolsTest`) -> fara erori.
- Ce ramane:
	- Optional: ajustare catalog norme (costuri unitare) pe baza datelor istorice reale din proiecte.

### 2026-07-01 - Checkpoint AI Tools (PDF oferta + acceptare rapida)
- Etapa: operationalizare flux comercial din deviz AI catre executie.
- Dovezi:
	- Ruta noua `quotes.pdf` genereaza fisier PDF dedicat pentru oferta/deviz.
	- Ruta noua `quotes.accept` permite marcarea rapida a ofertei ca `accepted` (cu timestamp de acceptare).
	- Lista de oferte include actiuni rapide: `PDF` si `Accepta`.
	- In Card 2 AI (`Deviz automat`), dupa commit apare buton direct `Descarca PDF oferta`.
- Validare:
	- `artisan test tests/Feature/ProjectAiToolsTest.php` -> passed (3/3, 60 assertions).
	- `npm run build` -> passed.
	- `get_errors` pe fisierele afectate -> fara erori.
- Ce ramane:
	- Optional: template PDF extins cu breakdown detaliat pe materiale/manopera/utilaje direct din payload-ul estimarii AI.

### 2026-07-01 - Checkpoint AI Tools (aliniere 1:1 pe logica carduri)
- Etapa: aliniere functionala completa dupa checklist backend pe 3 carduri.
- Dovezi:
	- Card `Poza factura`: adaugat `invoice_number` in flow (extract + revizuire UI + commit), plus servicu OCR configurabil (`mock`/`ocrspace`) cu fallback safe.
	- Card `Deviz automat`: commit-ul salveaza acum si `Document` de tip `estimate` in proiect (pe langa `Quote` + etape WBS).
	- Config servicii extins cu `invoice_ocr.driver` si `invoice_ocr.ocrspace_api_key`.
	- Schema extinsa: coloana noua `documents.invoice_number` + index.
- Validare:
	- `artisan migrate` -> passed.
	- `artisan test tests/Feature/ProjectAiToolsTest.php` -> passed (3/3, 65 assertions).
	- `npm run build` -> passed.
	- `get_errors` pe fisierele afectate -> fara erori.
- Ce ramane:
	- Optional: calibrare parsing OCR pe sabloane reale de facturi RO pentru cresterea acuratetii.

### 2026-07-01 - Checkpoint Oferta PDF profesionala (deviz)
- Etapa: upgrade output ofertare pentru Card `Deviz automat`.
- Dovezi:
	- PDF-ul ofertei include acum sectiuni profesionale cu liste detaliate:
		- A. Materiale (elemente listate una sub alta)
		- B. Manopera (elemente listate una sub alta)
		- sumar total cu TVA explicit.
	- Flow-ul AI transmite breakdown detaliat (`materials`, `labor`, `totals`) la salvarea ofertei pentru a alimenta PDF-ul.
	- TVA implicit a fost actualizat la 21% in fluxurile de ofertare (AI + manual quote create/edit).
- Validare:
	- `artisan test tests/Feature/ProjectAiToolsTest.php` -> passed (3/3, 67 assertions).
	- `npm run build` -> passed.
	- `get_errors` pe fisierele afectate -> fara erori.
- Ce ramane:
	- Optional: adaugare sectiune C. Utilaje cu itemizare completa in PDF, daca doresti separare explicita.

### 2026-07-01 - Checkpoint Dashboard (Calendar operational azi)
- Etapa: integrare planificare zilnica in dashboard, deasupra zonei `Atentie azi`.
- Dovezi:
	- Modul nou `Calendar azi` afisat in dashboard cu sumar operational (`evenimente`, `riscuri`) si data curenta.
	- 6 categorii active in mini-view operational:
		- Etape programate azi
		- Taskuri cu deadline azi
		- Utilaje rezervate azi
		- Subcontractori programati azi
		- Documente cu termen azi
		- Verificari / calitate programate azi
	- Datele sunt agregate din modulele existente si grupate pe categorii in backend-ul dashboard.
	- `Atentie azi` ramane zona de risc, iar `Calendar azi` devine zona de planificare.
- Validare:
	- `artisan test tests/Feature/DashboardFinancialInsightsTest.php` -> passed (1/1).
	- `npm run build` -> passed.
	- `get_errors` pe fisierele afectate -> fara erori.
- Ce ramane:
	- Optional: predictor AI de intarziere pe baza blocajelor si resurselor indisponibile.

### 2026-07-09 - Plan Initiativa (Trasabilitate resurse + automatizari comerciale)
- Etapa: planificare extensie enterprise peste modulele deja existente `materials`, `equipment`, `documents`, `material_invoices`, `quality_checks`, `tasks`, `stage_tasks`, `pilot_invites`.
- Problema rezolvata:
	- astazi exista evidenta de materiale, utilaje, facturi, verificari si taskuri, dar nu exista un lant unic de trasabilitate intre comanda -> livrare -> confirmari -> factura -> plata;
	- diferentele de cantitate si valoare nu sunt detectate automat;
	- partea comerciala are owner + follow-up + next step, dar nu are inca taskuri/reminder-uri comerciale generate sistemic.
- Decizie de arhitectura:
	- fisierele raman pe infrastructura existenta din `documents` pentru upload/download/PDF si permisiuni;
	- pentru trasabilitate introducem entitati dedicate, nu supraincarcam `documents` sau `material_invoices` cu logica de workflow;
	- taskurile si notificarile automate se bazeaza pe infrastructura existenta `tasks` + `OperationalReminderNotification`, extinsa cu evenimente comerciale si de diferente resurse.

#### Workstream A - Resurse: documente, trasabilitate, diferente

##### Faza R1 - Fundatie date + tipuri documente
- Scop: definim backbone-ul de date pentru trasabilitatea unui material/utilaj.
- Livrabile:
	- extindere tipuri documente pentru: `delivery_note`, `carrier_note`, `pump_note`, `resource_invoice`, `site_photo`, `receipt_confirmation`, `quantity_confirmation`, `quality_confirmation`;
	- tabel nou `resource_orders` pentru comanda de material/utilaj: proiect, etapa, resursa, furnizor, transportator, utilaj, cantitate comandata, pret, data livrare, responsabil, status;
	- tabel nou `resource_document_links` sau echivalent pentru a lega mai multe documente de o singura comanda/livrare;
	- tabel nou `resource_deliveries` pentru cantitatea declarata, cantitatea receptionata, cantitatea pompata/utilizata si observatii operative;
	- tabel nou `resource_confirmations` pentru confirmari separate: sef santier, responsabil executie, responsabil calitate, responsabil financiar.
- Criteriu de acceptare:
	- o singura comanda de beton poate avea atasate minim 4 documente diferite si 4 confirmari distincte, toate tenant-scoped.

##### Faza R2 - UI nou in Resurse: tab Documente
- Scop: introducere rapida si controlata a documentelor reale din santier.
- Livrabile:
	- tab nou `Documente` in zona Resurse, cu filtre pe proiect, etapa, material, utilaj, furnizor, status verificare, diferenta detectata;
	- formular unificat pentru upload document + metadata: numar aviz, furnizor, transportator, utilaj, cantitate declarata, cantitate livrata, observatii, poze;
	- workflow UI pentru `Confirmare receptie`, `Confirmare cantitate`, `Confirmare calitate`, `Validare financiara`;
	- timeline vizual pe fiecare livrare/comanda cu stare: creat -> livrat -> receptionat -> verificat -> validat financiar.
- Criteriu de acceptare:
	- se poate inregistra cap-coada cazul de beton: comanda 10 mc, aviz statie 10 mc, aviz pompa 8.5 mc, receptie santier 8.5 mc.

##### Faza R3 - Submodul Trasabilitate Materiale
- Scop: vizibilitate completa pe fluxul unui material.
- Livrabile:
	- pagina dedicata `Trasabilitate materiale` cu timeline si card de reconciliere;
	- legatura intre `resource_orders`, documente, consumuri din taskuri/rapoarte de etapa si facturi materiale;
	- sumar automat: comandat, livrat, pompat/utilizat, consumat, returnat, facturat, platit;
	- badge-uri de stare: `conform`, `in verificare`, `cu diferente`, `blocat la plata`.
- Criteriu de acceptare:
	- pentru un material se poate vedea dintr-un singur ecran daca diferenta este de livrare, consum sau facturare.

##### Faza R4 - Submodul Trasabilitate Utilaje
- Scop: reconciliere intre rezervare, ore lucrate si cost.
- Livrabile:
	- pagina dedicata `Trasabilitate utilaje` construita peste `equipment` + `stage_equipment`;
	- campuri noi pentru ore confirmate, cost/ora negociat, cost total confirmat, aviz utilaj/pompa si confirmari duale (sef santier + executie);
	- conectare cu cost tracking pentru vizibilitate in dashboard financiar.
- Criteriu de acceptare:
	- pentru un utilaj rezervat se poate compara rezervarea initiala cu orele confirmate si costul final aprobat.

##### Faza R5 - Motor automat de diferente + blocaje de plata
- Scop: detectie automata a pierderilor si erorilor financiare.
- Livrabile:
	- serviciu dedicat `ResourceDiscrepancyService` sau echivalent pentru reguli:
		- cantitate comandata != cantitate livrata;
		- cantitate livrata != cantitate pompa/utilaj;
		- cantitate pompa/utilaj != cantitate consumata;
		- aviz != factura;
		- factura != buget / plafon etapa;
	- tabel nou `resource_discrepancies` cu severitate, cauza probabila, owner si status de rezolvare;
	- creare automata de task intern + notificare responsabil la fiecare diferenta critica;
	- blocaj soft sau hard pe validarea financiara atunci cand diferenta depaseste pragul configurat.
- Criteriu de acceptare:
	- cazul 10 mc comandati / 8.5 mc pompati genereaza automat alerta, task si status `blocat la plata` pana la justificare.

##### Faza R6 - PDF si raportare executiva
- Scop: auditabilitate si raportare pentru management/client.
- Livrabile:
	- raport PDF `Trasabilitate resursa` cu timeline, documente, confirmari, diferente, poze si decizie financiara;
	- export CSV/XLSX pentru diferente deschise si reconciliere pe proiect/etapa/furnizor;
	- widget nou in dashboard/raportare: `Pierderi evitate`, `livrari cu diferente`, `facturi blocate`, `utilaje fara confirmare completa`.
- Criteriu de acceptare:
	- orice livrare poate fi exportata intr-un raport PDF unic pentru audit intern sau control client.

#### Workstream B - Comercial: actiuni automate, reminder-uri, taskuri

##### Faza C1 - Model comercial operational
- Scop: mutam CRM-ul comercial din stare informativa in stare executabila.
- Livrabile:
	- tabel nou `commercial_actions` pentru log de actiuni: apel, email, demo, oferta, follow-up, negociere, inchidere;
	- tabel nou `commercial_tasks` sau reutilizare `tasks` cu `category=commercial`, `entity_type=pilot_invite|tenant`, `entity_id`, `due_at`, `automation_source`;
	- reguli de owner comercial clare: owner implicit, fallback owner, reasignare.
- Criteriu de acceptare:
	- fiecare lead/comercial record poate avea istoric de actiuni si taskuri deschise urmaribile separat de taskurile operationale.

##### Faza C2 - Automatizari comerciale
- Scop: sistemul creeaza urmatorul pas fara interventie manuala la fiecare schimbare.
- Livrabile:
	- la `commercial_stage = contacted` fara `follow_up_at` -> generam reminder automat in 48h;
	- la `demo` programat -> generam task `pregatire demo` si reminder in ziua demo-ului;
	- la `trial` fara activare / onboarding incomplet -> task automat pentru owner comercial;
	- la `negotiation` fara actualizare X zile -> alerta de stagnare;
	- la `won` -> task automat de handoff catre onboarding/operational;
	- la `lost` -> reminder de reactivare optional la 30/60/90 zile.
- Criteriu de acceptare:
	- schimbarea etapei comerciale produce automat task/reminder-ul corect fara edit manual suplimentar.

##### Faza C3 - Inbox comercial + raportare
- Scop: vizibilitate clara pe ce trebuie facut azi de echipa comerciala.
- Livrabile:
	- widget nou in dashboard comercial: `Taskuri azi`, `follow-up restante`, `oportunitati stagnante`, `handoff-uri catre onboarding`;
	- filtre noi in `PilotInvites/Index`: doar cu reminder azi, doar fara next step, doar stagnante;
	- notificari centralizate in Notification Center pentru reminder-urile comerciale.
- Criteriu de acceptare:
	- owner-ul comercial poate intra dimineata si vedea direct lista de actiuni obligatorii, fara inspectie manuala lead cu lead.

#### Ordine recomandata de implementare
- Lot 1: R1 + R2.
	- motiv: introduce datele corecte si UI-ul minim pentru santier.
- Lot 2: R5 (partial, doar reguli comandat/livrat/facturat) + C1.
	- motiv: aduce valoare financiara imediata si baza comuna de taskuri automate.
- Lot 3: R3 + C2.
	- motiv: inchide fluxul materiale si comercialul automatizat.
- Lot 4: R4 + R6 + C3.
	- motiv: finalizeaza partea enterprise, audit si dashboard executiv.

#### Dependente si observatii de implementare
- Refolosim storage-ul din `DocumentController`, dar metadatele de trasabilitate trebuie mutate in request-uri si modele dedicate.
- `MaterialInvoice` ramane registrul financiar principal, dar are nevoie de legatura explicita catre livrare/comanda sau document-sursa.
- `QualityCheck` trebuie extins cu mod `materials` orientat pe receptie material, inclusiv poze si verdict de conformitate.
- `SendOperationalRemindersCommand` trebuie extins sau dublat cu un scheduler pentru reminder-uri comerciale si alerte de discrepanta.
- Pentru loturile cu upload foto/PDF trebuie pastrat acelasi model de permisiuni/policies si aceeasi validare de build/test ca la modulele recente.

#### Definitie de succes pentru initiativa
- zero plata fara reconciliere minima comanda -> documente -> confirmari -> factura;
- orice diferenta materiala/financiara critica devine alerta + task + responsabil;
- comercialul nu mai depinde de memorie individuala pentru follow-up;
- Modulia capata un flux auditabil enterprise de la lead pana la receptie si plata.

#### Urmatorul pas recomandat
- kickoff implementare cu Lot 1 (`R1 + R2`) pentru ca are cel mai bun raport impact / risc si foloseste maximum din infrastructura deja existenta.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (Lot 1 / increment 1)
- Etapa: fundatie date pentru trasabilitate resurse.
- Dovezi:
	- extinse tipurile `Document` pentru avize, confirmari si documente de resursa (`delivery_note`, `carrier_note`, `pump_note`, `resource_invoice`, `site_photo`, `receipt_confirmation`, `quantity_confirmation`, `quality_confirmation`);
	- adaugate modelele si migrarile noi pentru backbone-ul de trasabilitate:
		- `resource_orders`
		- `resource_deliveries`
		- `resource_confirmations`
		- `resource_document_links`
	- legaturi Eloquent minime adaugate in `Project`, `ProjectPhase`, `Material`, `Equipment`.
	- test nou dedicat: `ResourceTraceabilityFoundationTest` pentru:
		- salvare `delivery_note` in registrul existent de documente;
		- relatie comanda -> livrare -> confirmare -> document sursa.
- Validare:
	- `artisan test tests/Feature/ResourceTraceabilityFoundationTest.php` -> passed (2/2).
	- `get_errors` pe modelele noi si testul dedicat -> fara erori.
- Ce ramane:
	- incrementul urmator din Lot 1: request-uri, controller si UI minim pentru tabul `Documente Resurse`.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (Lot 1 / increment 2)
- Etapa: CRUD minim pentru registrul `Documente Resurse`.
- Dovezi:
	- controller nou `ResourceOrderController` cu primele actiuni `index`, `create`, `store`;
	- request nou `StoreResourceOrderRequest` cu validari pentru proiect, etapa si selectie corecta material/utilaj;
	- ruta noua `resource-orders.*` si intrare noua in meniul lateral `Resurse -> Documente resurse`;
	- pagini Inertia noi:
		- `ResourceOrders/Index`
		- `ResourceOrders/Create`
	- test nou `ResourceOrdersTest` pentru listare, creare si validarea relatiei proiect-etapa.
- Validare:
	- `npm run build` -> passed.
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (3/3).
	- `get_errors` pe controller, rute, pagini Vue si test -> fara erori.
- Ce ramane:
	- incrementul urmator: formularul de documente efective (aviz livrare / transportator / pompa) si legarea acestora in `resource_document_links`.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (Lot 1 / increment 3)
- Etapa: atasare documente reale in fluxul `Documente Resurse`.
- Dovezi:
	- `ResourceOrder` extins cu lista centralizata de tipuri documente permise pentru trasabilitate;
	- `StoreResourceOrderRequest` accepta acum `documents[]` cu titlu, tip, numar document, cantitati, observatii si fisier PDF/poza;
	- `ResourceOrderController` salveaza tranzactional comanda + documentele asociate in `documents` si `resource_document_links`;
	- formularul `ResourceOrders/Create` permite adaugarea dinamica de documente atasate direct din registrul resurse;
	- `ResourceOrders/Index` afiseaza si numarul de documente legate pe fiecare inregistrare;
	- `ResourceOrdersTest` acopera si scenariul cu upload de document si calcul automat al diferentei declarata vs livrata.
- Validare:
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (4/4).
	- `npm run build` -> in rulare / de confirmat in checkpointul curent.
	- `get_errors` pe model, request, controller, pagini Vue si test -> fara erori.
- Ce ramane:
	- incrementul urmator: detalierea confirmarii de receptie / cantitate / calitate si primul ecran de reconciliere pe livrare.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (Lot 1 / increment 4)
- Etapa: detaliu livrare + confirmari workflow + alerta automata.
- Dovezi:
	- `resource-orders.show` implementat cu pagina noua `ResourceOrders/Show` (timeline, documente legate, sumar discrepanta, date logistice);
	- endpoint nou `resource-orders.confirmations.update` pentru confirmarile rolurilor: sef santier, executie, calitate, financiar;
	- `ResourceOrders/Index` include acces direct catre pagina de detaliu;
	- automatizare la discrepanta: la diferenta pozitiva detectata in documente se genereaza task cu prioritate `high` + notificare `resource_discrepancy` catre responsabil;
	- `NotificationCenterController` include evenimentul nou `resource_discrepancy` in filtrele centrului de notificari;
	- testele `ResourceOrdersTest` acopera:
		- pagina de detaliu cu timeline/confirmari,
		- upload documente + calcul diferenta,
		- creare task + notificare automata,
		- update confirmare pe rol.
- Validare:
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (6/6).
	- `npm run build` -> passed (manifest actualizat cu `ResourceOrders/Show.vue`).
	- `get_errors` pe controller, pagina `Show` si test -> fara erori.
- Ce ramane:
	- incrementul urmator: reconciliere extinsa (comandat vs livrat vs pompa vs consum) cu praguri configurabile si status `blocat la plata`.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (Lot 1 / increment 5)
- Etapa: reconciliere extinsa cu prag de toleranta si blocare automata la plata.
- Dovezi:
	- status nou `blocked_payment` in `ResourceOrder` cu eticheta "Blocat la plata";
	- configurare noua `config/resources.php` cu `quantity_tolerance` (default `0.20`, configurabil din env);
	- `ResourceOrderController` calculeaza reconcilierea pe mai multe axe:
		- declarat vs livrat (din documente legate),
		- comandat vs livrat,
		- livrat vs pompa,
		- pompa vs consum,
	  aplicand regulile doar cand sursele necesare exista;
	- daca orice verificare depaseste pragul, comanda trece automat in `blocked_payment`;
	- task + notificare de discrepanta se emit doar pentru discrepante blocante (nu pentru abateri minore sub prag);
	- UI `ResourceOrders/Show` include panou de reconciliere, prag afisat si banner explicit cand plata este blocata;
	- UI `ResourceOrders/Index` coloreaza distinct statusul `blocked_payment`.
- Validare:
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (7/7).
	- `npm run build` -> passed.
	- `get_errors` pe controller/page/test -> fara erori.
- Ce ramane:
	- urmatorul pas: tranzitii de status pe flux de confirmari (`ordered` -> `verified` -> `financial_review` -> `approved`) cu reguli explicite pe roluri.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (Lot 1 / increment 6)
- Etapa: lifecycle automat pe confirmari + precedenta blocaj financiar.
- Dovezi:
	- `updateConfirmation` aplica automat statusul comenzii dupa fiecare confirmare pe baza regulilor de workflow;
	- reguli lifecycle implementate:
		- orice confirmare `rejected` => status `rejected`;
		- discrepanta blocanta activa => status `blocked_payment` (precedenta maxima);
		- toate confirmarile tehnice + financiar confirmat => `approved`;
		- toate confirmarile tehnice confirmate, fara financiar => `financial_review`;
		- minim o confirmare tehnica => `verified`;
		- fara confirmari => `ordered`.
	- teste extinse pentru:
		- tranzitie `verified` dupa prima confirmare tehnica,
		- `financial_review` dupa completare tehnica,
		- `approved` dupa confirmare financiara,
		- precedenta `blocked_payment` fata de fluxul de confirmari.
- Validare:
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (9/9).
	- `npm run build` -> passed.
	- push pe `origin/main` realizat cu commit `42d78f1`.

### 2026-07-09 - Checkpoint Comercial (automatizari reminder + task)
- Etapa: inchidere backlog comercial pentru reminder-uri si taskuri automate.
- Dovezi:
	- schema noua `commercial_tasks` pentru taskuri comerciale dedicate lead-urilor (`pilot_invites`);
	- model nou `CommercialTask` + relatie `PilotInvite->commercialTasks`;
	- `PilotInviteController` extins cu automatizare lifecycle:
		- creare task comercial automat la lead activ (`invited`, `contacted`, `demo_scheduled`, `trial_started`),
		- recalcul prioritate/scadenta pe baza `follow_up_at` / `demo_scheduled_at`,
		- anulare automata task deschis la `closed_won` / `closed_lost`,
		- notificare automata `commercial_follow_up` catre responsabil la creare task nou;
	- `NotificationCenterController` include `commercial_follow_up` in filtrele de evenimente;
	- UI `PilotInvites/Index` afiseaza sumarul taskului comercial activ (titlu, scadenta, prioritate).
- Validare:
	- `artisan test tests/Feature/PilotInvitesTest.php` -> passed (4/4).
	- `artisan test tests/Feature/PilotInvitesTest.php tests/Feature/ResourceOrdersTest.php` -> passed (13/13).
	- `npm run build` -> passed.
- Ce ramane:
	- optional v2: pagina dedicata `Taskuri comerciale` cu board pe stadii (`todo/in_progress/done/cancelled`) si SLA pe owner.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (detaliu: adaugare/stergere document)
- Etapa: completare workflow operational direct din pagina de detaliu a comenzii.
- Dovezi:
	- `resource-orders.show` include acum formular inline `Adauga document nou` (tip, numar, fisier, cantitati, note);
	- endpoint nou `resource-orders.documents.store` pentru atasare document pe comanda existenta;
	- endpoint nou `resource-orders.documents.destroy` pentru stergere document legat salvat gresit;
	- fiecare document listat in detaliu are actiune `Sterge`;
	- la adaugare/stergere document se recalculeaza automat:
		- reconcilierea cantitatilor,
		- statusul lifecycle (`ordered/verified/financial_review/approved/rejected/blocked_payment`),
		- taskul de discrepanta (creare/update/anulare) fara duplicate pe aceeasi comanda.
- Validare:
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (11/11).
	- `npm run build` -> passed.
	- `get_errors` pe controller/show/test/routes -> fara erori.
- Ce ramane:
	- optional v2: jurnal audit explicit pe actiunile de stergere document (cine/ora/motiv) in timeline.

### 2026-07-09 - Checkpoint Trasabilitate Resurse (index: stergere inregistrare)
- Etapa: optimizare operare rapida direct din registru.
- Dovezi:
	- buton nou `Sterge` in lista `Documente resurse`, langa `Detalii`;
	- endpoint nou `resource-orders.destroy` activ in routing pentru stergere din index;
	- `ResourceOrderController@destroy` aplicat tenant-scoped (fara acces cross-tenant);
	- confirmare UI inainte de stergere pentru prevenirea actiunilor accidentale.
- Validare:
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (12/12).
	- `npm run build` -> passed.
	- `get_errors` pe index/controller/routes/test -> fara erori.

### 2026-07-09 - Checkpoint Audit Istoric Documente/Confirmari
- Etapa: consolidare control anti-manipulare pe comenzi de resurse.
- Dovezi:
	- loguri persistente in `access_audit_logs` pentru actiuni critice:
		- `resource_order.document_attached`,
		- `resource_order.document_deleted`,
		- `resource_order.confirmation_updated`,
		- `resource_order.deleted`,
		- `resource_order.created`;
	- metadata audit include campuri operationale (tip document, numar document, cantitati, rol confirmare, status inainte/dupa);
	- `resource-orders.show` afiseaza sectiune noua `Istoric audit` cu actor, timestamp si context principal de actiune.
- Validare:
	- `artisan test tests/Feature/ResourceOrdersTest.php` -> passed (12/12, 100 assertions).
	- `get_errors` pe fisierele modificate -> fara erori.
- Ce ramane:
	- optional v2: filtru dedicat in pagina globala de audit pentru `resource_order.*` + export audit punctual pe comanda.

## 13. Plan Enterprise Rapoarte & Exporturi (v2) - propunere executabila

Obiectiv: sa ridicam modulul actual de exporturi de la nivel bun la nivel enterprise, fara regressii in fluxurile existente.

### 13.1. Principii de livrare
1. Livrare incrementala pe faze mici, fiecare faza cu valoare directa in productie.
2. Compatibilitate 100% cu exporturile existente (CSV/XLSX/PDF/pachet complet/email).
3. Toate exporturile noi trebuie auditate automat (actor, filtru, proiect, perioada, format).
4. Rapoartele critice pentru materiale/avize au prioritate maxima.

### 13.2. Prioritati (Must / Should / Could)

Must (Q1):
1. Cautare globala unificata pe rapoarte.
2. Template-uri predefinite one-click.
3. Intervale rapide (Today/7/30/90/Year).
4. Audit complet exporturi.
5. Rapoarte materiale & avize.

Should (Q2):
1. Preview inainte de export.
2. Tab-uri dedicate pe tip de raport (proiect/etapa/echipa/contractor/resurse/utilaje/calitate/financiar).
3. Fluxuri automate email cu atasamente multiple + mesaj personalizat.

Could (Q3):
1. PDF managerial cu grafice avansate multi-pagina si branding extins per tenant.
2. Biblioteca de template-uri custom per companie.

### 13.3. Faze propuse (8 saptamani)

Faza A (Sapt. 1-2) - Foundation UX + Query Layer
1. Search bar global in pagina exporturi (query unificata peste module).
2. Preset interval rapid: today, last_7d, last_30d, last_90d, this_year.
3. Standardizare contract filtre (aceleasi chei pentru CSV/XLSX/PDF/email).
4. KPI header: nr. rezultate, module incluse, ultima rulare.

Definition of Done:
1. Cautarea globala filtreaza toate cardurile/modulele dintr-un singur input.
2. Intervalele rapide functioneaza identic pe toate exporturile.

Faza B (Sapt. 3-4) - Template-uri predefinite one-click
1. Introducere template-uri:
	- Proiect complet
	- Financiar complet
	- Calitate & Defecte
	- Utilaje & Resurse
	- Taskuri & Progres
	- Cost vs Buget
	- Materiale & Avize
2. Model de date pentru template (slug, descriere, module incluse, format implicit).
3. Buton one-click: genereaza export fara configurare manuala.

Definition of Done:
1. Fiecare template ruleaza dintr-un click.
2. Rezultatele sunt consistente intre rulare manuala si template.

Faza C (Sapt. 5) - Audit exporturi complet automat
1. Log obligatoriu la fiecare export:
	- cine a exportat
	- ce a exportat (tip/template)
	- cand
	- ce filtre a folosit
	- ce proiect/tenant/interval
2. Filtre noi in Audit pentru `export.*` + preset rapid.
3. Export CSV pentru audit exporturi.

Definition of Done:
1. 100% din exporturile noi/vechi lasa urme in audit.
2. Se poate reconstrui complet contextul unui export din log.

Faza D (Sapt. 6) - Materiale & Avize (verticala critica)
1. Raport Materiale comandate vs livrate.
2. Raport Avize statie vs avize pompa.
3. Raport Cantitate livrata vs consumata.
4. Raport Diferente materiale (cu prag de alerta).
5. Raport Costuri materiale vs buget.
6. Raport Utilaje pe etape cu cost real.

Definition of Done:
1. Rapoartele se exporta in CSV/XLSX/PDF.
2. Diferentele se pot urmari pe proiect, etapa si perioada.

Faza E (Sapt. 7) - Preview + Layout enterprise
1. Preview inainte de export (rezumat date + module incluse + volum estimat).
2. Tab-uri dedicate pe tip raport:
	- Proiect, Etapa, Echipa, Contractor, Resurse, Utilaje, Calitate, Financiar.
3. Carduri vizuale cu KPI-uri (status, risc, cost, progres).

Definition of Done:
1. Utilizatorul poate valida ce exporta inainte de generare.
2. Navigarea pe rapoarte devine orientata pe business, nu pe format.

Faza F (Sapt. 8) - Automatizari email avansate
1. Fluxuri multiple per tenant (zilnic/saptamanal/lunar).
2. Atasamente multiple PDF + XLSX in acelasi email.
3. Mesaj custom si branding companie per sablon.
4. Rapoarte combinate in acelasi job programat.

Definition of Done:
1. Scheduler genereaza si livreaza rapoarte fara interventie manuala.
2. Brandul companiei este aplicat coerent in toate livrarile.

### 13.4. Arhitectura functionala (recomandare)
1. `ReportingQueryService` - unifica filtrele si agregarile pe module.
2. `ReportTemplateRegistry` - mapare template -> set de module + formate.
3. `ReportPreviewBuilder` - calculeaza preview (count/KPI/estimare dimensiune).
4. `ExportAuditLogger` - punct unic de audit pentru toate exporturile.
5. `ScheduledReportRunner` - executa joburile automate de email/export.

### 13.5. Backlog tehnic minim
1. Backend:
	- API/filter contract unificat,
	- endpoint preview,
	- endpoint run template,
	- extindere audit metadata pentru exporturi.
2. Frontend:
	- search global,
	- quick ranges,
	- preset cards,
	- tabs pe domenii,
	- preview modal.
3. Testing:
	- feature tests pe template-uri,
	- snapshot tests pentru PDF,
	- contract tests pentru filtre,
	- audit assertions obligatorii per export.

### 13.6. KPI de succes
1. Timp mediu generare raport redus cu minim 40% prin template-uri one-click.
2. Minim 95% exporturi cu audit complet (target operational 100%).
3. Minim 70% utilizare pe template-uri predefinite vs export manual.
4. Zero incidente de "export neauditat" in productie.

### 13.7. Primii 5 pasi imediati (start implementare)
1. Definim schema unica de filtre + intervale rapide (contract backend/frontend).
2. Implementam search bar global in UI exporturi.
3. Livram template-ul critic `Materiale & Avize` primul.
4. Adaugam audit complet pe toate actiunile de export (inclusiv scheduled).
5. Introducem preview simplu (numar inregistrari + module incluse) inainte de export.

### 2026-07-09 - Checkpoint Exporturi Enterprise (Faza A incremental)
- Etapa: livrare foundation UX pe exporturi (quick ranges + global search + preview + one-click templates).
- Dovezi:
	- filtru `quick_range` standardizat in backend (`today`, `last_7d`, `last_30d`, `last_90d`, `this_year`) cu fallback pe `from/to`;
	- cautare globala unificata (`global_search`) compatibila cu alias legacy `q`;
	- endpoint nou `exports.preview` cu payload de preview (`rows_count`, sample, filtre active, timestamp);
	- audit automat la preview (`export_type=preview`, `format=system`);
	- sectiune noua UI `Rapoarte predefinite (one-click)` cu template-uri:
		- Proiect complet,
		- Financiar complet,
		- Calitate & Defecte,
		- Utilaje & Resurse,
		- Taskuri & Progres,
		- Cost vs Buget,
		- Materiale & Avize;
	- actiuni one-click per template: XLSX, PDF, CSV, Preview.
- Validare:
	- `artisan test tests/Unit/ExportFilterTest.php` -> passed.
	- `artisan test tests/Feature/EnterpriseExportsTest.php` -> passed.
	- `npm run build` -> passed.
- Ce ramane (urmatorul increment):
	- tab-uri dedicate pe domenii de raportare (proiect/etapa/echipa/contractor/resurse/utilaje/calitate/financiar);
	- extindere scheduler pentru fluxuri multiple cu atasamente combinate;
	- raportare verticala Materiale & Avize cu indicatori comparativi dedicati (comandat vs livrat, aviz statie vs pompa, consum vs livrat).