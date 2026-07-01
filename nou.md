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