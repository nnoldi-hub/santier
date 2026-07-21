# Modul "Organizare Șantier" - plan de dezvoltare pe faze

Sursa initiala a conceptului: `plan_santier.md` (notele utilizatorului, fisier local,
necomis). Acest document e varianta lucrata - verificata contra codului existent,
impartita in faze livrabile, si actualizata pe masura ce avansam. **Se actualizeaza
dupa fiecare faza incheiata**, la fel cum s-a facut cu `nou.md` pe parcursul acestei
sesiuni.

## 1. Ce este modulul

Un tab nou in proiect, "Organizare Șantier", care raspunde la intrebarea "suntem
pregatiti sa incepem santierul?" pe 8 axe (echipe, subcontractori, materiale, utilaje,
logistica, documente, buget, timeline), plus un scor de pregatire agregat si export
PDF/XLSX. Se pozitioneaza intre WBS (ce vrem sa facem) si executie (task-uri, resurse,
calendar - ce se intampla efectiv).

## 2. Verificari facute contra codului existent (nu presupuneri)

- Niciun tabel `site_*` nu exista azi (confirmat din `php artisan migrate:status` -
  63 migratii, toate `Ran`, nimic cu acest prefix). Toate cele 8 entitati din plan
  sunt genuin noi.
- **Puncte de reutilizare confirmate**: `ProjectPhase` (WBS-ul existent, cu
  `contractor_id`, `parent_id`), `Team`/`TeamMember`, `Contractor` (are deja `type`
  cu `subcontractor`/`internal_team`/etc., dar **nu are camp de status contract** -
  `contract_status` e genuin nou), `Material`, `Equipment` +
  `App\Support\EquipmentCostEstimator` (deja construit, refolosibil direct pentru
  faza de utilaje), `Document` (are `type`/`payment_status`, dar **nu are concept de
  "aviz"/"autorizatie" ca tip distinct de conformitate** - checklist-ul de
  conformitate e genuin nou), `Quote` (pentru costuri estimate in faza de buget).
- **`ProjectAiToolsController`** exista deja (rute `projects.ai.estimate.generate`,
  `.commit`, `.invoice.extract`, `.invoice.commit`, `.budget-alert`) - confirmat prin
  cod ca sunt **euristici locale, nu apeluri catre un LLM extern**. Cele 3 "AI Tools"
  din plan (planificare echipe/materiale/timeline) urmeaza acelasi tipar - euristici
  pe date proprii, etichetate "AI", nu integrare cu un provider extern.
- Export PDF/XLSX are deja infrastructura solida de reutilizat:
  `App\Support\ExportDatasetBuilder` + `App\Exports\EnterpriseWorkbookExport` +
  `resources/views/exports/managerial-pdf.blade.php` (cu grafice, adaugate in aceasta
  sesiune) - "SitePlanningExporter" din plan nu porneste de la zero.
- **Recomandare de arhitectura**: `Projects/Show.vue` are deja 1444 randuri - modulul
  nou merge intr-o **pagina Inertia dedicata** (`resources/js/Pages/SiteOrganization/
  Index.vue` sau similar, ruta `projects/{project}/organizare`), cu sub-tab-uri
  interne pentru cele 8 sectiuni, NU inghesuit in `Show.vue`. Acelasi tipar ca
  paginile de trasabilitate din aceasta sesiune (pagini separate, nu sectiuni noi in
  fisiere deja mari).

## 3. Faze de dezvoltare

Fiecare faza = un ciclu complet (plan aprobat -> cod -> `npm run build` -> teste ->
commit -> push -> checkpoint in acest document), la fel ca restul initiativelor din
`nou.md`. O faza pe runda, in ordine (fiecare faza urmatoare se poate baza pe ce e
deja construit).

| # | Faza | Domeniu | Status |
|---|------|---------|--------|
| 1 | Fundatie + Echipe & specialitati | Pagina noua "Organizare Șantier" (ruta, nav, layout cu sub-taburi) + `site_staff_plans` (planificare echipe/specialitati per etapa WBS) | **Facut** |
| 2 | Subcontractori | `site_contractor_plans` (contract, disponibilitate, suprapuneri) | **Facut** |
| 3 | Materiale | `site_material_plans` (necesar, furnizor, termene livrare, risc) | **Facut** |
| 4 | Utilaje | `site_equipment_plans` (refoloseste `EquipmentCostEstimator`) | **Facut** |
| 5 | Logistica | `site_logistics_plans` (acces, depozitare, zone siguranta, restrictii) | **Facut** |
| 6 | Documente & conformitate | `site_compliance_plans` (checklist contracte/avize/autorizatii cu semafor) | **Facut** |
| 7 | Buget initial | `site_budget_plans` (linii bugetare manuale + rezumat auto din materiale/utilaje) | **Facut** |
| 8 | Rezumat & scor de pregatire | `SiteReadinessCalculator` (agrega toate fazele 1-7 intr-un scor 0-100 + blocaje, calculat live) | **Facut** |
| 9 | AI Tools organizare | `SitePlanningAIAdvisor` - 3 euristici (necesar oameni, necesar materiale, timeline realist), calculat live in `SiteOrganizationController` | **Facut** |
| 10 | Export plan organizare | `SitePlanningExporter` - PDF + XLSX, refoloseste sablonul PDF si `CollectionSheetExport` existente | **Facut** |
| 11 | Aprobare plan + activare executie | Bara "Aproba planul": campuri noi `plan_approved_at`/`plan_approved_by` pe proiect, istoric `site_plan_approvals`, blocare editare pe toate cele 7 domenii, generare automata de `Task`/`ResourceOrder`/`StageEquipment` + `ProjectPhase.contractor_id` | **Facut** |
| 12 | Cost & ore manopera | `hourly_rate` pe `site_staff_plans` + `LaborCostEstimator` (oglinda la `EquipmentCostEstimator`) - ore/cost estimat per plan de personal, semnal de suprapunere echipa, integrare in rezumatul de buget si in export | **Facut** |
| 13 | Deviz -> SiteMaterialPlan | `commitEstimate()` genereaza automat planuri de materiale (nu doar personal/utilaje) din lista de materiale a devizului, legate de etapa "Aprovizionare materiale" | **Facut** |
| 14 | Sablon WBS + task-uri din reteta | `recipe_wbs_stages` (nume + task-uri implicite) - devizul genereaza sub-etape proprii de executie (nu doar una generica) + `StageTask` automate pe fiecare, `stage_role` inlocuieste potrivirea fragila de nume/pozitie | **Facut** |

**Ordinea nu e arbitrara**: fazele 2-7 sunt independente intre ele (pot fi reordonate
dupa prioritate de business), dar faza 8 (scorul de pregatire) are nevoie de cel putin
cateva din ele deja construite ca sa agrege ceva real, iar fazele 9-11 au nevoie de
toate datele deja existente ca sa fie utile. Daca prioritatea de business cere alta
ordine intre fazele 2-7, o schimbam fara probleme - doar 8/9/10/11 raman ultimele.

**Faza 11 - context aditional** (adaugata dupa recitirea `plan_santier.md`, care
descrie explicit acest flux la "PASUL 4-5", neinclus initial in fazele 1-10):
verificat in cod (agent de explorare) ca **nu exista nicio infrastructura pentru
asta azi** - `Project.status` are doar `draft/active/paused/completed/cancelled`
(fara "Planificare"/"Executie activa"), nu exista tabel de istoric de status (doar
`AccessAuditLog`, generic, pentru audit de securitate - reutilizabil ca inspiratie,
nu ca atare), nu exista niciun mecanism de blocare a editarii dupa aprobare. Mai
important: **conversia automata plan -> real NU e o mapare curata 1:1** pentru 4 din
cele 6 domenii - `SiteStaffPlan` -> `Task` lipseste `assigned_to`/`title` (task e per
persoana, planul e pe headcount), `SiteContractorPlan` -> `PhaseTeamAssignment`
cere `team_id` pe care planul de subcontractor nu il are, `SiteMaterialPlan` ->
`ResourceOrder` lipseste `unit_price`/`ordered_unit`/`responsible_user_id`, iar
pentru logistica si buget nu exista deloc o entitate "reala" de conversie (checklist/
KPI). Doar `SiteEquipmentPlan` -> `StageEquipment` e aproape 1:1. Aceste goluri
trebuie rezolvate explicit in planul de implementare al Fazei 11 (decizii: de unde
vine `assigned_to`, cum se aloca `team_id` din contractor, ce valori implicite
pentru campurile lipsa) - nu sunt simplificari care se pot ignora, ca la fazele
anterioare.

## 4. Cum lucram pe fiecare faza

Acelasi flux stabilit in aceasta sesiune:
1. Alegem faza (intrebare directa la inceput de sesiune, ca la backlog-ul din `nou.md`).
2. `EnterPlanMode` cu cercetare in cod (agenti de explorare daca domeniul e nou) inainte
   de a scrie planul - nu presupunem, verificam.
3. Implementare dupa aprobare, `npm run build`, teste PHP scrise (dar nerulabile din
   acest mediu - fara PHP CLI accesibil), commit + push.
4. **Actualizam tabelul din sectiunea 3** (Status: Neinceput -> In lucru -> Facut, cu
   o nota scurta despre ce s-a livrat si ce a ramas in afara scopului), la fel cum s-a
   actualizat `nou.md` dupa fiecare initiativa.

## 5. Progres

### Faza 1 - Fundatie + Echipe & specialitati (Facut, 2026-07-14)
- Pagina noua `projects/{project}/organizare` (`SiteOrganization/Index.vue`), cu bara
  de sub-taburi pentru toate cele 8 domenii viitoare - doar "Echipe & specialitati"
  e functional, restul afiseaza `EmptyState` cu mesaj clar ca urmeaza.
- Tabel nou `site_staff_plans` (`App\Models\SiteStaffPlan`) + `SiteOrganizationController`
  (index + store/update/destroy pentru planuri de personal), validare scoped corect
  pe tenant/proiect (`Rule::exists` cu `where`, nu doar `exists:table,id` simplu).
- Buton nou "Organizare Șantier" in `Projects/Show.vue`, langa "Editeaza".
- Ramas explicit in afara scopului: conversia unui plan in alocare reala
  (`phase_team_assignments`), calcul automat de suprapuneri/risc - vezi plan.
- Test `tests/Feature/SiteStaffPlanTest.php` (creare, validare, stergere, izolare
  tenant).

### Faza 2 - Subcontractori (Facut, 2026-07-14)
- Tab-ul "Subcontractori" devine functional: tabel nou `site_contractor_plans`
  (`App\Models\SiteContractorPlan`) - evaluare candidati subcontractori per etapa
  (status contract draft/semnat/lipsa, disponibilitate ok/risc/conflict), separat de
  `ProjectPhase.contractor_id` (asignarea reala), la fel ca la Faza 1.
- `parallel_projects_count` calculat live in `SiteOrganizationController` (nu stocat
  redundant) - numara etapele active ale aceluiasi subcontractor pe alte proiecte.
- Ramas explicit in afara scopului: checklist de documente per subcontractor (merge
  in Faza 6), conversie automata in `phase.contractor_id`.
- Test `tests/Feature/SiteContractorPlanTest.php` (creare, validare, stergere,
  izolare tenant).

### Faza 3 - Materiale (Facut, 2026-07-14)
- Tab-ul "Materiale" devine functional: tabel nou `site_material_plans`
  (`App\Models\SiteMaterialPlan`) - necesar planificat de material per etapa
  (cantitate, furnizor, lead-time in zile, data comanda/livrare planificata, risc),
  separat de `Material` (catalog) si de `ResourceOrder` (comanda reala), la fel ca la
  Fazele 1-2.
- Ramas explicit in afara scopului: conversia unui plan in `ResourceOrder` real,
  calcul automat de risc din `lead_time_days` vs. data etapei (candidat pentru Faza 9
  - AI Tools).
- Test `tests/Feature/SiteMaterialPlanTest.php` (creare, validare, stergere,
  izolare tenant).

### Faza 4 - Utilaje (Facut, 2026-07-14)
- Tab-ul "Utilaje" devine functional: tabel nou `site_equipment_plans`
  (`App\Models\SiteEquipmentPlan`) - necesar planificat de utilaj per etapa
  (cantitate, perioada de folosire, risc), separat de `Equipment` (catalog) si de
  `StageEquipment` (rezervarea reala), la fel ca la Fazele 1-3.
- Reutilizeaza direct `App\Support\EquipmentCostEstimator` (type-hint relaxat la
  `StageEquipment|SiteEquipmentPlan`, fara alt refactor) - fiecare plan afiseaza live
  `estimated_cost` si `reserved_days` calculate cu acelasi tipar folosit deja in
  exportul de trasabilitate utilaje.
- `reserved_elsewhere_count` calculat live in `SiteOrganizationController` - numara
  rezervarile reale `StageEquipment` ale aceluiasi utilaj cu perioada suprapusa, ca
  semnal informativ de risc (fara blocare la salvare, spre deosebire de
  `StageEquipmentController`).
- Ramas explicit in afara scopului: conversia unui plan in `StageEquipment` real,
  blocare stricta la suprapunere.
- Test `tests/Feature/SiteEquipmentPlanTest.php` (creare, validare, stergere,
  izolare tenant).

### Faza 5 - Logistica (Facut, 2026-07-14)
- Tab-ul "Logistica" devine functional: tabel nou `site_logistics_plans`
  (`App\Models\SiteLogisticsPlan`) - 4 categorii (acces, depozitare, zona de
  siguranta, restrictie), fiecare cu titlu, locatie descriptiva, note de capacitate
  si nivel de risc. Domeniu genuin nou - confirmat prin cautare in cod ca nu exista
  niciun tabel real de executie de care sa ne separam (spre deosebire de Fazele 1-4).
- Ramas explicit in afara scopului: harta vizuala/plan de amplasament (schema
  grafica a zonelor) - doar text structurat in aceasta faza.
- Test `tests/Feature/SiteLogisticsPlanTest.php` (creare, validare, stergere,
  izolare tenant).

### Faza 6 - Documente & conformitate (Facut, 2026-07-14)
- Tab-ul "Documente" devine functional: tabel nou `site_compliance_plans`
  (`App\Models\SiteCompliancePlan`) - checklist de contracte/avize/autorizatii, cu
  status semafor (valid/expira curand/expirat/lipsa) si legatura optionala cu o
  etapa WBS si cu un subcontractor. Confirmat prin cautare in cod ca `Document` nu
  are niciun concept de "aviz de conformitate"/"autorizatie"/expirare - domeniu
  genuin nou.
- Ramas explicit in afara scopului: calcul automat al statusului semafor din
  `due_date` (candidat pentru Faza 9 - AI Tools), atasare de fisiere la elementele
  de conformitate.
- Test `tests/Feature/SiteCompliancePlanTest.php` (creare, validare, stergere,
  izolare tenant, izolare `contractor_id` pe tenant).

### Faza 7 - Buget initial (Facut, 2026-07-14)
- **Decizie de design fata de presupunerea initiala din roadmap** ("agrega costuri
  din fazele 2-6 + manopera"): verificat in cod ca doar `SiteMaterialPlan` (via
  `Material.unit_price`) si `SiteEquipmentPlan` (via `EquipmentCostEstimator`, deja
  construit la Faza 4) au o sursa de cost fiabila. `Team`/`Contractor` nu au niciun
  camp de tarif - singurul tarif din aplicatie e `TeamMember.hourly_rate` (per
  membru, nu per echipa/headcount planificat), insuficient pentru un calcul automat
  de manopera. Deci `site_budget_plans` e un tabel de **linii bugetare manuale**
  (manopera, subcontractori, logistica, conformitate, rezerva, altele - fara
  materiale/utilaje, ca sa nu se dubleze), plus un **rezumat calculat live**
  (`buildBudgetSummary()` in `SiteOrganizationController`) care aduna cost materiale
  auto + cost utilaje auto + suma liniilor manuale, comparat cu `Project.total_budget`.
- Tab-ul "Buget" devine functional cu un card de rezumat (materiale/utilaje/manual/
  total/buget alocat/diferenta) deasupra formularului si tabelului de linii.
- Ramas explicit in afara scopului: calcul automat de manopera din
  `TeamMember.hourly_rate` (neconcludent), conversia bugetului estimat in
  `Project.total_budget` real, integrare cu `Quote`/`CostTrackingController` (raman
  mecanisme separate).
- Test `tests/Feature/SiteBudgetPlanTest.php` (creare, validare, stergere, izolare
  tenant, plus un test dedicat care verifica agregarea corecta a rezumatului bugetar
  din materiale + linii manuale via `assertInertia`).

### Faza 8 - Rezumat & scor de pregatire (Facut, 2026-07-14)
- **Decizie de design fata de roadmap**: fara tabel `site_readiness_summary` -
  scorul e calculat live la fiecare incarcare a paginii de `App\Support\
  SiteReadinessCalculator::calculate()`, exact ca `budgetSummary` de la Faza 7 (fara
  risc de desincronizare, fara nevoie de invalidare cache).
- Tab-ul "Rezumat" devine functional: scor general 0-100 + eticheta semafor
  (Pregatit/Necesita atentie/Nepregatit), scor pe fiecare din cele 7 domenii
  (clickabil -> navigheaza direct la tab-ul respectiv), lista de blocaje concrete.
- Euristici locale simple (medie neponderata a 7 sub-scoruri, fara integrare cu un
  LLM extern) - acelasi tipar ca `ProjectAiToolsController`.
- Limitare asumata explicit: un domeniu fara niciun plan primeste scor 0 chiar daca
  domeniul respectiv nu e relevant pentru proiect - nuantarea "N/A" ramane candidat
  pentru Faza 9 (AI Tools).
- Test `tests/Feature/SiteReadinessCalculatorTest.php` (calculul in izolare pentru 3
  scenarii + un test HTTP care confirma `readiness.score` in payload-ul Inertia).

### Faza 9 - AI Tools organizare (Facut, 2026-07-14)
- **Decizie de design fata de roadmap**: NU extinde `ProjectAiToolsController` (acel
  controller gestioneaza actiuni cu efecte secundare - creeaza `Quote`/`ProjectPhase`/
  `Document` reale). Sugestiile din aceasta faza sunt pur informative, calculate live
  in `SiteOrganizationController::index()` (acelasi tipar ca `SiteReadinessCalculator`
  de la Faza 8), fara rute noi, fara efecte secundare.
- `App\Support\SitePlanningAIAdvisor` - catalog hardcodat per tip de etapa WBS
  (12 din cele 13 tipuri, exclus `custom`), acelasi tipar ca `normDefinition()` din
  `ProjectAiToolsController`. 3 euristici: personal necesar (etape fara
  `SiteStaffPlan`), materiale necesare (etape fara `SiteMaterialPlan`), timeline
  realist (durata planificata in afara intervalului tipic din catalog).
- Tab nou "AI Tools" in pagina Organizare Șantier, cu 3 sectiuni read-only (fara
  formular, fara conversie automata a sugestiilor in planuri reale).
- Ramas explicit in afara scopului: estimare bazata pe date istorice reale
  (`Task`/`StageTask` nu au camp de ore/durata estimata - catalog static in aceasta
  faza), orice integrare cu un LLM extern.
- Test `tests/Feature/SitePlanningAIAdvisorTest.php` (4 scenarii izolate + un test
  HTTP care confirma `aiSuggestions` in payload-ul Inertia).

### Faza 10 - Export plan organizare (Facut, 2026-07-14)
- **Decizie de design fata de roadmap**: NU extinde `ExportDatasetBuilder`/
  `EnterpriseWorkbookExport` (legate strict de un `match` inchis, filter-driven,
  gandit pentru rapoarte multi-proiect la nivel de portofoliu). In schimb:
  `App\Support\SitePlanningExporter::buildSections()` construieste 10 sectiuni
  (cate una per domeniu, plus buget-linii/buget-rezumat/rezumat-scor/AI-sugestii)
  o singura data, refolosite atat pentru PDF (direct in `sections`, sablonul
  `exports/managerial-pdf.blade.php` ramas neschimbat) cat si pentru XLSX
  (`App\Exports\SitePlanningWorkbookExport`, care instantiaza
  `App\Exports\Sheets\CollectionSheetExport` existenta per sectiune).
- Logica de asamblare a datelor (8 domenii + `budgetSummary` + `readiness` +
  `aiSuggestions`) extrasa din `index()` intr-o metoda noua `gatherPlanningData()`,
  refolosita de pagina si de cele 2 actiuni noi de export - fara interogari
  duplicate.
- Branding + audit refolosite ca atare (`AppSetting::allForTenant()`,
  `DocumentBranding::resolveLogoPath()`, `App\Support\ExportAudit::log()`) - acelasi
  tipar ca `ExportController::workbook()`/`managerialPdf()`.
- Doua butoane noi ("Export PDF"/"Export XLSX") in header-ul paginii Organizare
  Șantier - linkuri directe, fara flux de generare-apoi-descarcare.
- Ramas explicit in afara scopului: grafice in PDF (ar necesita extinderea unui
  `match` inchis de categorii), istoric de exporturi dedicat pentru acest tip.
- Test `tests/Feature/SitePlanningExportTest.php` (export PDF/XLSX cu succes,
  izolare tenant).

### Faza 11 - Aprobare plan + activare executie (Facut, 2026-07-14)
- **Corectie fata de analiza initiala din runda trecuta**: `SiteContractorPlan` NU
  se converteste in `PhaseTeamAssignment` (care cere `team_id`, inexistent pe plan)
  - mapare corecta, deja stabilita la Faza 2: scriere directa pe
  `ProjectPhase.contractor_id`, pentru planurile cu `contract_status === 'signed'`
  si `phase_id` setat.
- Campuri noi `plan_approved_at`/`plan_approved_by` pe `projects` (separat de
  `status`, ca sa nu ating logica existenta din restul aplicatiei) + tabel nou
  `site_plan_approvals` (istoric aprobare/anulare).
- Butonul "Aproba planul" (bara persistenta, vizibila pe toate tab-urile) genereaza
  automat: un task de coordonare per plan de personal (`Task`, `assigned_to` gol -
  nu se pot fabrica alocari per persoana dintr-un headcount), `ResourceOrder`
  (status `draft`) per plan de material (completat cu `unit_price`/`ordered_unit`
  din catalogul `Material`), `StageEquipment` per plan de utilaj CU etapa (planurile
  fara etapa sunt sarite - `stage_id` e obligatoriu la nivel de schema),
  `contractor_id` pe etapele cu subcontractor semnat. Daca `total_budget` era gol,
  se completeaza cu `budgetSummary.total_estimated`.
- Dupa aprobare, toate cele 7 domenii de planificare devin needitabile (verificare
  server-side `abort_if(..., 423)` pe toate cele 21 de metode store/update/destroy,
  plus dezactivare vizuala in Vue).
- Ramas explicit in afara scopului: deduplicare la re-aprobare dupa o anulare,
  stergerea automata a elementelor de executie la anulare, schimbarea
  `Project.status`.
- Test `tests/Feature/SitePlanApprovalTest.php` (generare artefacte, plan de
  utilaj fara etapa sarit corect, blocare editare dupa aprobare, anulare aprobare,
  imposibilitatea de a aproba de doua ori, izolare tenant).

### Faza 12 - Cost & ore manopera (Facut, 2026-07-20)
- Continuare directa a unei analize pe 7 arii cerute de utilizator ("Resurse
  necesare pe proiect" - materiale/manopera/utilaje/calitate/defecte), verificate
  cu agenti de explorare inainte de plan. "Necesar de manopera" era cel mai mare
  gol real: `SiteStaffPlan` nu avea niciun camp de ore/cost.
- **Decizie de design**: `hourly_rate` e un camp propriu, manual, pe
  `SiteStaffPlan` (coloana noua) - NU derivat din `TeamMember.hourly_rate`
  (per-persoana, nu per-plan pe headcount, si echipa e adesea neasignata inca
  in faza de planificare) - exact limitarea deja documentata la Faza 7. Acelasi
  tipar ca `RecipeLaborItem.hourly_rate` din modulul Retetar (sesiunea anterioara).
- `App\Support\LaborCostEstimator` (nou) - oglinda exacta la
  `EquipmentCostEstimator`: ore estimate = zile planificate × 8h/zi × necesar
  oameni; cost = ore × tarif orar.
- `SiteOrganizationController::staffPlansWithEstimates()` (nou, oglinda la
  `equipmentPlansWithEstimates()`) - adauga `estimated_hours`/`estimated_cost`
  pe fiecare plan, plus `team_overlap_count` (doar cand planul are `team_id` -
  numara `PhaseTeamAssignment` reale suprapuse pentru aceeasi echipa, afisat ca
  badge informativ, la fel ca `reserved_elsewhere_count` la utilaje - fara
  blocare la salvare).
- Cost manopera auto integrat in rezumatul de buget (`labor_cost`, inclus in
  `total_estimated`) si in exportul PDF/XLSX.
- **Efect colateral rezolvat**: `SiteBudgetPlan` avea deja o categorie manuala
  `'labor'` (din Faza 7, inainte sa existe calcul automat) - ar fi dublat costul
  in rezumat. Nu s-a sters categoria (ar fi stricat validarea pe randuri
  existente) - liniile cu `category=labor` sunt acum excluse explicit din suma
  `manual_cost`, cu o nota vizibila in UI.
- Ramas explicit in afara scopului: productivitate echipa, disponibilitate
  echipa calculata (spre deosebire de utilaje, `Team` tot nu are un
  `availability_status`) - candidati pentru o faza viitoare separata.
- Teste: `tests/Feature/SiteStaffPlanTest.php` (2 teste noi - calcul
  ore/cost, `team_overlap_count`), `tests/Feature/SiteBudgetPlanTest.php`
  (test existent actualizat sa reflecte excluderea categoriei `labor` + test
  nou pentru `labor_cost` automat).

### Faza 13 - Deviz -> SiteMaterialPlan (Facut, 2026-07-20)
- Continuare directa a Fazei 12 - `commitEstimate()` genera deja automat
  `SiteStaffPlan`/`SiteEquipmentPlan` din manopera/utilaje la commit, dar nu
  si `SiteMaterialPlan` din materiale, desi lista completa (cu `material_id`)
  era deja calculata in `estimate_details.materials`. Inconsistenta
  confirmata la analiza initiala pe 7 arii.
- Gol mic descoperit la implementare: validarea din `commitEstimate()` nu
  includea `estimate_details.materials.*.material_id` in whitelist - desi
  `generateEstimate()` il trimite pe fiecare rand, ar fi fost eliminat
  silentios de `$request->validate()`. Adaugat.
- Spre deosebire de personal/utilaje (legate de etapa "Executie"),
  materialele se leaga de etapa **"Aprovizionare materiale"** (semantic
  corect - materialele se comanda/livreaza inainte de executie) -
  `planned_order_date`/`planned_delivery_date` preiau
  start/end date-ul acelei etape (din sprintul de timeline, Faza anterioara
  sesiunii curente).
- Acelasi tipar ca personal/utilaje: fara deduplicare, respecta
  `plan_approved_at` (skip silentios, nu blocheaza restul commit-ului), fara
  `supplier_name`/`lead_time_days` (nicio sursa de date in deviz pentru ele).
- Teste: `tests/Feature/ProjectAiToolsTest.php` extins (assert pe cele 2
  planuri de materiale generate + datele legate de etapa corecta; testul de
  plan blocat extins sa verifice si `site_material_plans`).

### Faza 14 - Sablon WBS + task-uri din reteta (Facut, 2026-07-20)
- Ultimul gol din analiza pe 7 arii: reteta nu putea defini propriile
  sub-etape de executie - devizul genera mereu exact 5 etape fixe, una
  singura generica "Executie - {sablon}", fara niciun task individual.
- Tabel nou `recipe_wbs_stages` (`App\Models\RecipeWbsStage`) - nume + ordine
  + `default_tasks` (json, listă simplă de titluri) - **doar pentru
  sub-etapele de executie**, Pregatire/Aprovizionare/Control calitate/Predare
  raman fixe. Compatibilitate totala cu retetele existente: fara sablon WBS
  definit, devizul genereaza exact ca inainte (o singura etapa "Executie").
- **Corectie de robustete descoperita la cercetare**: loop-ul din
  `commitEstimate()` folosea un map pozitional fragil
  (`$durationsByIndex[$index]`) si cauta etapele de personal/utilaje/
  materiale prin potrivire de nume hardcodata (`where('name', 'like',
  'Executie%')`) - ambele s-ar fi rupt cu un numar variabil de sub-etape.
  Inlocuite cu un tag explicit `stage_role` pe fiecare etapa (calculat la
  generare, trimis la commit), capturat direct in interiorul loop-ului de
  creare a etapelor - functioneaza corect indiferent de cate sub-etape de
  executie exista sau daca unele etape existau deja dintr-un commit anterior.
- Durata etapelor de executie (una sau mai multe) se imparte cu formula
  simpla deja folosita peste tot in cod: `max(1, ceil(total / numar_etape))`
  - acceasi durata pentru fiecare sub-etapa, nu garanteaza suma exacta
  (poate usor supraestima), acceptabil pentru o estimare.
- Task-urile implicite (`default_tasks`) devin `StageTask` (status `todo`,
  nu `Task` - potrivire structurala mai buna: legat direct de `stage_id`,
  fara `tenant_id`/materiale/checklist, exact ce trebuie pentru un task
  generat automat) create pe fiecare sub-etapa noua la commit.
- Planurile de personal/utilaje se leaga acum de **prima** etapa cu
  `stage_role = 'executie'` (inainte era "singura" etapa de executie).
- Teste: `tests/Feature/RecipeManagementTest.php` (creare/actualizare reteta
  cu etape proprii + task-uri implicite), `tests/Feature/ProjectAiToolsTest.php`
  (test nou cu 2 sub-etape proprii - confirma 6 `ProjectPhase` create in loc
  de 5, `StageTask` generate cu titlurile corecte, durata impartita corect,
  planurile de personal/utilaje legate de prima sub-etapa; testul principal
  actualizat sa verifice `stage_role` pe cele 5 etape fixe, confirmand
  compatibilitatea cu retetele fara sablon WBS).
- Ramas explicit in afara scopului: Pregatire/Aprovizionare/Control
  calitate/Predare nu pot fi suprascrise din reteta; fara UI in modalul de
  deviz care sa afiseze `default_tasks` inainte de commit (task-urile devin
  vizibile abia dupa commit, in `stage-tasks.index`/Gantt).

### Faza 15 - Pontaj (ore reale) + cost real vs estimat + suprapuneri calendar (Facut, 2026-07-20)
- Din propunerea "Manopera avansata" (5 subteme), utilizatorul a ales sa
  scopam sesiunea la pontaj + cost real, plus o extindere mica a
  suprapunerilor in `TeamCalendar`. Disponibilitate/concediu echipa
  (necesita model nou de absenta) si productivitate echipa (fara unitate de
  masura clara in acest model de date) raman explicit pentru o faza
  viitoare.
- Tabel nou `site_staff_time_entries` (`App\Models\SiteStaffTimeEntry`) -
  jurnal de pontaj per `SiteStaffPlan`: data + ore lucrate totale
  (persoana-ore, comparabile direct cu `estimated_hours`) + nota optionala.
  Doar adaugare/stergere, fara editare - jurnal, nu formular editabil.
- **Pontajul nu e blocat de `abortIfPlanLocked()`**, spre deosebire de toate
  celelalte operatii din `SiteOrganizationController` - planul se aproba
  *inainte* de executie, iar pontajul se completeaza *in timpul* executiei,
  deci trebuie sa functioneze si dupa aprobare. Verificat explicit printr-un
  test dedicat.
- `actual_hours`/`actual_cost` calculate la citire in
  `staffPlansWithEstimates()` (nu stocate), acelasi tipar ca
  `estimated_hours`/`estimated_cost`/`team_overlap_count`; cost real =
  `sum(hours_worked) * hourly_rate` (acelasi tarif ca la cel estimat, fara
  tarif separat per intrare).
- `buildBudgetSummary()` capata `labor_cost_actual`, afisat separat in
  cardul de buget, **fara** sa intre in `total_estimated` (ar amesteca o
  cifra reala cu estimari pentru materiale/utilaje).
- `TeamCalendarController`: `overlap_count` per `PhaseTeamAssignment`
  (aceeasi echipa, interval de date suprapus, exclude propriul id) - acelasi
  tipar de query ca `team_overlap_count`, afisat ca badge in
  `TeamCalendar/Index.vue` + tile nou in sumar
  (`summary.assignments_with_overlap`).
- Teste: `tests/Feature/SiteStaffPlanTest.php` (pontaj adaugat/sters,
  calculul orelor/costului real, pontaj functional pe plan aprobat, izolare
  tenant), `tests/Feature/SiteBudgetPlanTest.php` (`labor_cost_actual` in
  sumar, exclus din total), `tests/Feature/TeamCalendarTest.php` (2 asignari
  suprapuse → `overlap_count` corect pe fiecare).
- Ramas explicit in afara scopului: disponibilitate/concediu echipa,
  productivitate echipa, editare de intrari de pontaj, cost real pentru
  materiale/utilaje.

### Faza 16 - Preturi "inghetate" pe proiect (Facut, 2026-07-20)
- Din auditul cerut de utilizator ("cum planificam materiale/manopera/
  utilaje/furnizori/termene per proiect fara sa se amestece intre ele") a
  iesit un risc real: costul de materiale si utilaje nu era salvat pe planul
  proiectului, ci calculat live la fiecare citire prin join catre catalogul
  global (`Material.unit_price`, `Equipment.cost_per_hour`) - o schimbare de
  pret in catalog modifica retroactiv bugetul unor proiecte deja finalizate.
  Manopera era deja facuta corect (`SiteStaffPlan.hourly_rate` salvat pe
  plan) - model urmat acum si pentru materiale/utilaje.
- **Bug confirmat si reparat in acelasi loc**: la comiterea unui deviz
  automat, tariful de manopera calculat se pierdea la salvare -
  `SiteStaffPlan::create()` nu-l transmitea, desi era deja validat si
  disponibil - planurile de personal generate automat ieseau cu cost 0.
- `unit_price` nou pe `SiteMaterialPlan`, `hourly_rate` nou pe
  `SiteEquipmentPlan` - snapshot salvat o singura data la creare, niciodata
  recalculat automat din catalog dupa aceea. Migratii cu backfill pentru
  randurile existente (din pretul curent de catalog - cel mai bun proxy
  posibil, istoricul real nu poate fi reconstituit).
- 3 puncte de creare seteaza acum snapshot-ul: formular manual (fallback din
  catalog daca pretul nu e trimis explicit), "Aplica reteta" (Faza 13), si
  commit-ul de deviz AI (unde s-a reparat si bug-ul de manopera).
  `EquipmentCostEstimator::estimate()` foloseste `SiteEquipmentPlan.
  hourly_rate` cand e setat, altfel cade pe rata live - neschimbat pentru
  `StageEquipment` (alt flux, in afara riscului identificat).
- Materialele capata si ele `estimated_cost` calculat
  (`materialPlansWithEstimates()`, mirror pe `equipmentPlansWithEstimates()`
  deja existent) - `buildBudgetSummary()` devine uniform, toate cele 3
  costuri citesc `estimated_cost` de pe planul propriu, nimic nu mai
  calculeaza live din catalog.
- UI: coloanele "Pret unitar"/"Cost estimat" pe tabelul de materiale (mirror
  pe utilaje), camp de pret/tarif prefilled automat din catalog la alegerea
  materialului/utilajului (poti suprascrie inainte de salvare). Export
  PDF/XLSX (`SitePlanningExporter::materialsSection()`) capata aceleasi 2
  coloane.
- Teste: `tests/Feature/SiteMaterialPlanTest.php` si `tests/Feature/
  SiteEquipmentPlanTest.php` (pret/tarif implicit din catalog, pret
  "inghetat" dupa schimbare de catalog), `tests/Feature/
  SiteMaterialPlanRecipeApplicationTest.php` (acelasi test pentru "Aplica
  reteta"), `tests/Feature/SiteBudgetPlanTest.php` (bugetul unui proiect
  ramane neschimbat dupa o schimbare de pret in catalog),
  `tests/Feature/ProjectAiToolsTest.php` (dupa commit, tariful de manopera
  si preturile de materiale sunt populate corect, nu 0).
- Ramas explicit in afara scopului: modul de Furnizori (catalog dedicat),
  folosirea efectiva a `lead_time_days` pentru calculul automat al datei de
  comanda, buffer pentru neprevazute pe termene - toate discutate, raman
  pentru o faza viitoare.

### Faza 17 - Modul Furnizori materiale (Facut, 2026-07-20)
- Golul ramas explicit din Faza 16: "furnizor" pe planul de materiale
  (`SiteMaterialPlan.supplier_name`) era doar text liber, fara catalog, fara
  istoric, fara reutilizare - spre deosebire de Subcontractori
  (`Contractor`), care au modul CRUD complet.
- **Tipar de mirror ales: `Material`/`MaterialController`/`MaterialPolicy`,
  nu `Contractor`/`ContractorPolicy`** - `ContractorPolicy` verifica
  permisiuni Spatie granulare generate din `IamSeeder::
  buildPermissionList()`; `MaterialPolicy` e simpla verificare de tenant,
  fara permisiuni noi - exact ce trebuie pentru un catalog simplu, fara sa
  ating `IamSeeder.php`.
- Modul nou `Supplier` (tabel `suppliers`, soft-deletes): `name*`,
  `contact_name`, `phone`, `email`, `notes`, `active` - CRUD complet
  (`SupplierController`, pagini `Suppliers/Index|Create|Edit.vue`), catalog
  global tenant-wide, nou in sidebar sub "Resurse → Catalog resurse".
- **`supplier_name` (text liber) exista in 5 locuri independente** in
  aplicatie (`SiteMaterialPlan`, `Material.supplier`, `Equipment`,
  `MaterialInvoice`, `ResourceOrder`) - doar `SiteMaterialPlan` e vizat
  acum. Celelalte raman neatinse - consolidarea lor ar fi un proiect
  separat, mult mai mare.
- `SiteMaterialPlan` capata `supplier_id` (FK nullabil) **alaturi de**
  `supplier_name` existent, nu il inlocuieste - `supplier_name` propaga deja
  in `ResourceOrder::create()` la conversia unui plan in comanda si in
  exportul PDF/XLSX, ambele ramase neschimbate. Alegerea unui furnizor din
  catalog completeaza automat `supplier_name` (acelasi tipar "snapshot" ca
  `unit_price`/`hourly_rate` din Faza 16) - editabil manual dupa aceea
  pentru furnizori ad-hoc fara catalog.
- Teste: `tests/Feature/SupplierManagementTest.php` (nou - creare/
  actualizare/stergere, validare `name`, izolare tenant), `tests/Feature/
  SiteMaterialPlanTest.php` (alegerea unui furnizor completeaza automat
  numele, numele ramane "inghetat" daca furnizorul e redenumit ulterior).
- Ramas explicit in afara scopului: consolidarea celorlalte 4 campuri
  "furnizor" text liber din aplicatie, istoric de preturi per furnizor,
  legatura furnizor-material (ce materiale ofera fiecare furnizor).

### Faza 18 - Lead-time -> data comanda + buffer neprevazute pe termene (Facut, 2026-07-20)
- Ultimele 2 goluri mici ramase din auditul de planificare a resurselor.
- **Lead-time**: `lead_time_days` exista deja pe `SiteMaterialPlan` dar
  nu era folosit nicaieri. Acum, la `storeMaterialPlan`/`updateMaterialPlan`,
  daca `planned_delivery_date` si `lead_time_days` sunt ambele prezente si
  `planned_order_date` NU a fost trimis explicit, se calculeaza server-side
  (acelasi tipar de fallback ca `unit_price`/`hourly_rate` din Faza 16).
  Formularul din Organizare Santier face acelasi calcul client-side la
  completarea datei de livrare + lead-time, editabil manual dupa aceea.
- **Buffer neprevazute**: `buffer_days` nou pe `ProjectPhase` (editabil din
  acelasi formular unde se editeaza deja start/end/durata,
  `Projects/Show.vue` + `ProjectPhaseController::update()`) - **fara**
  auto-populare la generarea automata din deviz AI (decizie deliberata a
  omului per etapa cu risc, la fel ca "Rezerva" pe buget, nu o presupunere
  a sistemului) si **fara** motor de reprogramare in cascada (schimbarea
  unei etape nu impinge automat etapele urmatoare - confirmat ca nu exista
  nicaieri in aplicatie o asemenea logica, ar fi fost un proiect mult mai
  mare decat cerut).
- Impact vizibil, fara recalcul de date: badge "+N zile buffer" in
  `Projects/Show.vue` si `Wbs/Index.vue` langa data de sfarsit; in
  `Gantt/Index.vue`, bara etapei capata un segment suplimentar haşurat dupa
  `end_date`, latime proportionala cu `buffer_days` (`phaseBufferBarStyle()`,
  mirror pe `phaseBarStyle()` existent) - nu e tragabil, drag-ul ramane
  neschimbat.
- Teste: `tests/Feature/SiteMaterialPlanTest.php` (calcul automat + pastrare
  valoare explicita), `tests/Feature/ProjectPhaseUpdateTest.php`
  (`buffer_days` se salveaza, respins daca e negativ), `tests/Feature/
  GanttTest.php` (nou - primul test care exercita `gantt.index`, confirma
  ca `buffer_days` apare in raspuns; `/gantt` nu avea niciun test inainte).
- Ramas explicit in afara scopului: motor de reprogramare in cascada,
  auto-populare buffer din deviz AI, buffer in exporturi PDF/XLSX
  (`SitePlanningExporter` nu are coloane de date pentru etape azi).

### Faza 19 - Consolidare catalog Furnizori (Facut, 2026-07-20)
- Ultimul gol ramas din audit: `supplier_name`/`supplier` existau ca text
  liber independent in 5 locuri. Faza 17 a legat `SiteMaterialPlan` de
  catalog; aceasta faza extinde acelasi tipar la celelalte 4:
  `Material.supplier`, `Equipment.supplier_name`,
  `MaterialInvoice.supplier_name`, `ResourceOrder.supplier_name`.
- **`supplier_id` (FK nullabil catre `suppliers`) adaugat alaturi de** (nu in
  locul) fiecarui camp text existent - acelasi tipar de snapshot ca la Faza
  16/17: alegerea unui furnizor din catalog completeaza automat campul text
  propriu, editabil manual dupa aceea pentru furnizori ad-hoc.
- **`ResourceDocumentLink.supplier_id`** adaugat si el (fara UI proprie) -
  copiat alaturi de `supplier_name` la cele 2 puncte unde `ResourceOrder`
  isi duplica deja datele catre documentele atasate.
- **Propagare `SiteMaterialPlan` -> `ResourceOrder`**: la conversia
  planului in comanda (`SiteOrganizationController.php`), `supplier_id` se
  propaga acum si el, alaturi de `supplier_name`.
- **Bug adiacent reparat pe aceeasi linie**: acel loc de conversie
  calcula costul comenzii din pretul live de catalog
  (`$plan->material?->unit_price`) in loc de snapshot-ul inghetat de pe
  plan (`$plan->unit_price`, din Faza 16) - inconsecvent cu restul
  aplicatiei dupa Faza 16, reparat acum.
- **Descoperire in timpul implementarii**: `EquipmentPolicy` si
  `MaterialInvoicePolicy` folosesc permisiuni Spatie granulare
  (`equipment.create`, `finance.create`), spre deosebire de
  `MaterialPolicy` (verificare simpla de tenant, folosita ca model pentru
  `SupplierPolicy` la Faza 17) - testele noi au nevoie de
  `$this->seed(IamSeeder::class)` pentru utilizatorul de test, la fel ca
  `EquipmentManagementTest.php` deja existent.
- Teste: `tests/Feature/SupplierCatalogLinkingTest.php` (nou - 8 teste, 2
  per model: completare automata + inghetare dupa redenumire furnizor,
  acelasi tipar ca la Faza 17).
- Ramas explicit in afara scopului: filtre de furnizor pe paginile de
  listare, istoric de preturi per furnizor, redenumirea coloanei
  `Material.supplier` la `supplier_name` (ar sparge exporturile fara
  beneficiu real).

### Faza 20 - Calitate 2.0: checklist din reteta, poze obligatorii, semnatura, raport agregat (Facut, 2026-07-21)
- Singura arie mare ramasa complet neatinsa din propunerea initiala pe 7
  arii a utilizatorului (restul erau deja construite in 19 faze +
  rafinari). 4 capabilitati distincte cerute explicit.
- **Checklist implicit din reteta**: `recipes.default_checklist` (json
  nullable) - acelasi tipar ca `RecipeWbsStage.default_tasks` (Faza 14),
  dar coloana direct pe `Recipe` (o singura lista plata, fara sub-etape).
  Sectiune noua in `Recipes/Create.vue`/`Edit.vue` (textarea, un item pe
  linie). In `QualityChecks/Create.vue`/`Edit.vue`: dropdown "Reteta
  (optional)" + buton "Aplica checklist" - client-side, adauga randuri
  `{text, done:false}` in `form.checklist` (deja o coloana JSON
  functionala), editabile dupa - fara endpoint nou, mirror pe integrarea
  retetei din `Tasks/Create.vue`.
- **Poze obligatorii**: tabel nou `quality_check_photos` (FK cascade catre
  `quality_checks`) + model `QualityCheckPhoto`. Upload multiplu
  (`photos[]`, max 6, `image|mimes:jpg,jpeg,png,webp|max:8192`), mirror pe
  bucla din `ResourceOrderController::persistLinkedDocuments()`. Regula de
  "obligatoriu" e in `StoreQualityCheckRequest::after()`: respinge cu
  eroare pe `photos` daca `status` e `passed`/`failed` si nu exista nicio
  poza (nici incarcata acum, nici deja existenta pe rand la update) -
  doar la starile finale, nu la `pending`/`in_progress`.
- **Semnatura digitala** (optionala - doar pozele au fost cerute explicit
  ca obligatorii): 3 coloane noi pe `quality_checks` (`signature_path`,
  `signed_by_name`, `signed_at`). Componenta noua
  `resources/js/Components/SignaturePad.vue` - canvas simplu cu pointer
  events (~90 linii), fara librarie noua de desen, consecvent cu
  principiul de a nu adauga dependente cand nu e nevoie. La submit,
  canvas-ul devine `data:image/png;base64,...`, decodat si salvat
  server-side ca fisier PNG (`QualityCheckController::persistSignature()`).
- **Raport agregat**: ruta noua `GET quality-checks/report?project_id=`,
  metoda noua `QualityCheckController::projectReport()` - toate
  verificarile unui proiect intr-un PDF nou
  (`quality_checks/project-report.blade.php`), cu sumar (total/conform/
  neconform/in asteptare) in antet - reutilizeaza `DocumentBranding::
  resolve()` si `ExportAudit::log()` ca restul exporturilor. Buton "Export
  raport agregat" pe `QualityChecks/Index.vue`, activ cand filtrul de
  proiect e selectat. Raportul PDF individual existent
  (`quality_checks/pdf.blade.php`) a fost extins cu sectiuni de poze si
  semnatura (bonus mic, acelasi loc).
- **Bug pre-existent descoperit si reparat pe aceeasi linie**: testul
  `QualityChecksTest::test_quality_check_can_be_created` nu trimitea
  `reception_type`, camp obligatoriu de la introducerea receptiilor
  partiale/finale - testul trecea din intamplare pana acum printr-un bug
  secundar in mesajul de eroare al PHPUnit/Laravel care masca eroarea
  reala de redirect; corectat prin adaugarea campului in payload-ul de
  test (nu are legatura cu Calitate 2.0, dar era in acelasi fisier).
- Teste: `tests/Feature/RecipeManagementTest.php` (+2 - creare/actualizare
  reteta cu `default_checklist`), `tests/Feature/QualityChecksTest.php`
  (+4 - poza obligatorie la Conform/Neconform respinsa fara poza si
  acceptata cu poza, stergere poza, decodare+salvare semnatura),
  `tests/Feature/QualityReportPdfTest.php` (nou - raportul agregat
  raspunde 200 cu PDF pentru un proiect, izolare tenant pe 404).
- Ramas explicit in afara scopului: legatura automata `ProjectPhase` ->
  `Recipe` (ar necesita `recipe_id` stocat pe etapa la commit-ul devizului
  AI - schimbare separata, mai mare), editare/redesenare a unei semnaturi
  deja salvate (doar inlocuire completa), compresie/optimizare imagini la
  upload, raport agregat filtrat pe etapa (doar pe proiect, in aceasta
  faza).
