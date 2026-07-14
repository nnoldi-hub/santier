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
| 11 | Aprobare plan + activare executie | Buton "Aproba planul" in tab-ul Rezumat: tranzitie status proiect, istoric de aprobare, blocare editare planuri, generare automata de artefacte reale de executie (`Task`, `PhaseTeamAssignment`, `ResourceOrder`, `StageEquipment`) din cele 6 domenii de planificare | Neinceput |

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
