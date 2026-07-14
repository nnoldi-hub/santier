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
| 6 | Documente & conformitate | `site_compliance_plans` (checklist contracte/avize/autorizatii cu semafor) | Neinceput |
| 7 | Buget initial | `site_budget_plans` (agrega costuri din fazele 2-6 + manopera) | Neinceput |
| 8 | Rezumat & scor de pregatire | `site_readiness_summary` + `SiteReadinessCalculator` (agrega toate fazele 1-7 intr-un scor 0-100 + blocaje) | Neinceput |
| 9 | AI Tools organizare | `SitePlanningAIAdvisor` - 3 euristici (necesar oameni, necesar materiale, timeline realist), extinde `ProjectAiToolsController` | Neinceput |
| 10 | Export plan organizare | `SitePlanningExporter` - PDF + XLSX, extinde infrastructura de export existenta | Neinceput |

**Ordinea nu e arbitrara**: fazele 2-7 sunt independente intre ele (pot fi reordonate
dupa prioritate de business), dar faza 8 (scorul de pregatire) are nevoie de cel putin
cateva din ele deja construite ca sa agrege ceva real, iar fazele 9-10 au nevoie de
toate datele deja existente ca sa fie utile. Daca prioritatea de business cere alta
ordine intre fazele 2-7, o schimbam fara probleme - doar 8/9/10 raman ultimele.

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
