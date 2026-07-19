# Modul "Rețete" (consum specific materiale) - plan de dezvoltare

Sursa initiala: utilizatorul a vrut retete de consum pentru doua tipuri de
subiect - o operatie de lucru (ex: "Zugravit" consuma 0.15 L vopsea/mp) sau un
material compus (ex: "Beton C25/30" = 300 kg ciment + 0.7 mc pietris + 0.5 mc
nisip / mc). Confirmat cu utilizatorul (`AskUserQuestion`): ambele tipuri de
subiect, integrare atat pe Task (auto-completare materiale) cat si in
Organizare Santier (planificare Materiale), gestionare prin pagina dedicata SI
creare rapida din context.

## Status: Facut (2026-07-20)

- **Prima relatie polimorfa din aplicatie** (`morphTo`) - o rețetă apartine fie
  unui `TaskTemplate` (operatie de lucru), fie unui `Material` (material
  compus). Morph map inregistrat in `AppServiceProvider::boot()` cu
  `Relation::morphMap()` - **NU** `enforceMorphMap()`, care ar fi blocat orice
  alta relatie polimorfa din aplicatie care nu e explicit in map (ex:
  `User::notifications()`, folosit de sistemul de notificari Laravel) - gasit
  si reparat in timpul testarii reale, inainte de commit.
- Tabele noi `recipes` (subject_type/subject_id, nume, unitate de baza) +
  `recipe_items` (material_id, quantity_per_unit). Fara camp persistent de
  "cantitate de lucru" pe `Task`/`ProjectPhase` - ramane input efemer, doar in
  formular, la aplicarea unei retete.
- `App\Http\Controllers\RecipeController` - CRUD complet (pagina dedicata,
  `resources/js/Pages/Recipes/*.vue`) + `quickCreate()` (creare rapida din
  context, folosita din formularul de Task).
- **Integrare Task**: `TaskController` expune rețeta atasata unui sablon (daca
  exista) in payload-ul `taskTemplates`; pe `Tasks/Create.vue`/`Edit.vue`, la
  alegerea unui sablon cu rețetă apare un input "Cantitate lucrare" + buton
  "Aplica reteta" care calculeaza si adauga automat randuri in "Consum
  materiale" (client-side, fara endpoint nou - salvarea foloseste rutele
  `tasks.store`/`tasks.update` deja existente). Sablon fara rețetă -> link
  "+ Reteta pentru acest sablon" (modal cu randuri repetabile material+
  cantitate).
- **Integrare Organizare Santier**: `SiteOrganizationController::applyMaterialRecipe()`
  (nou) - primeste `recipe_id`+`phase_id` (optional)+`work_quantity`, genereaza
  un `SiteMaterialPlan` per component al retetei cu cantitatea calculata.
  Bloc nou "Aplica reteta" pe tab-ul Materiale din `SiteOrganization/Index.vue`,
  deasupra formularului existent de adaugare manuala.
- Link "+ Reteta pentru acest material" pe `Materials/Edit.vue`, pentru
  retetele de tip material compus (creare doar din pagina dedicata, nu s-a
  duplicat editorul complex intr-un modal a doua oara pentru cazul mai rar).
- Teste: `RecipeManagementTest` (CRUD ambele tipuri de subiect, izolare
  tenant), `RecipeQuickCreateTest` (creare rapida din context), 
  `SiteMaterialPlanRecipeApplicationTest` (aplicare corecta, blocare la plan
  aprobat, izolare tenant). Auto-completarea pe Task nu are endpoint nou -
  fara test backend dedicat (logica e client-side).

## In afara scopului
Fara expandare recursiva (o reteta de material compus folosita ca item in alta
reteta nu se expandeaza automat inca un nivel), fara camp persistent de
"cantitate de lucru" pe `ProjectPhase`/`Task`, fara import/export de retete in
masa, fara pagina de management dedicata a "cantitatii de lucru" per etapa.

## Extindere: Deviz AI ghidat de retete (2026-07-18)

Generatorul de deviz automat ("Deviz automat din dimensiuni", pe pagina unui
proiect) calcula anterior costurile dintr-un catalog hardcodat pe 3 "tipuri de
lucrare" (`fence`/`foundation`/`plastering`/`custom`), complet independent de
retete. Utilizatorul nu intelegea de unde vin cifrele si voia un deviz corect
estimat + etape de lucru profesionale.

- `ProjectAiToolsController::generateEstimate()` foloseste acum un
  `TaskTemplate` ales din catalog (in loc de un enum fix) - daca sablonul ales
  nu are inca o reteta atasata, generarea e blocata cu raspuns
  `422 {needs_recipe: true, task_template_id, task_template_title}`, iar
  interfata (`Projects/Show.vue`) afiseaza un link direct "+ Reteta pentru
  sablon" catre `recipes.create` cu `subject_type`/`subject_id` preseta.
- Costul materialelor se calculeaza direct din `RecipeItem.quantity_per_unit *
  cantitate_lucrare * factor_complexitate * Material.unit_price`. Costurile de
  manopera/utilaje nu mai vin dintr-un catalog - au devenit inputuri manuale
  simple (RON/unitate) in formular.
- Etapele WBS generate sunt acum standard pentru orice deviz: `Pregatire`,
  `Aprovizionare materiale`, `Executie - {numele sablonului}`,
  `Control calitate`, `Predare` (inainte erau liste custom per tip de lucrare).
- `TaskTemplate::forEstimatePicker()` (metoda statica noua pe model) inlocuieste
  metoda privata duplicata `TaskController::taskTemplatesPayload()` - folosita
  acum si de `ProjectController::show()` pentru noul prop `taskTemplates`.

## Extindere: Reteta completa - manopera + utilaje + timpi (2026-07-20)

Utilizatorul a propus o arhitectura pe 3 niveluri (Resurse -> Retetar ->
Planificare) in 4 sprinturi. Verificare in cod (agent de cercetare): nivelul
Resurse era deja complet, iar Planificarea avea deja suprapunere semnificativa
cu ce se propunea (7 tabele `Site*Plan`, Gantt/WBS real, calculator de risc
`SiteReadinessCalculator`, pipeline PDF existent). Singurul gol real era la
Retetar: `Recipe`/`RecipeItem` capturau doar consum de materiale. S-a ales
directia "lean" (extindere, nu schema paralela), inceput cu acest sprint.

- Tabele noi `recipe_labor_items` (recipe_id, `role` text liber, hours_per_unit,
  hourly_rate - autonom, NU legat de un `Team` anume, fiindca o reteta e un
  sablon generic, nu depinde de echipa care o executa) si
  `recipe_equipment_items` (recipe_id, equipment_id FK obligatoriu catre
  `Equipment`, hours_per_unit - costul se citeste live din
  `Equipment.cost_per_hour`, nu se duplica pe rand, exact ca la materiale).
- Coloane noi `drying_hours`/`curing_hours` pe `recipes` (timpi ficsi, nu
  proportionali cu cantitatea - de asta stau pe reteta, nu pe un item).
- Nu s-a adaugat un camp separat de "timp executie" - va fi calculat din orele
  de manopera in viitorul `RecipeCalculatorService` (sprint urmator).
- `RecipeController::store()`/`update()` creeaza/inlocuiesc si randurile de
  manopera/utilaje, in aceeasi tranzactie ca materialele. `create()`/`edit()`
  primesc un prop nou `equipment` (catalogul de utilaje al tenantului).
  `quickCreate()` (creare rapida din context) ramane neschimbat - doar
  materiale, ca sa nu complice fluxul efemer de pe `Tasks/Create.vue`.
- `Recipes/Create.vue`/`Edit.vue` capata 2 blocuri noi de randuri repetabile
  (acelasi tipar ca "Materiale necesare") + 2 inputuri de timpi.
  `Recipes/Index.vue` arata numarul de randuri de manopera/utilaje pe fiecare
  card, ca sa se vada dintr-o privire care retete sunt "complete".

### In afara scopului (sprinturi viitoare)
Nu s-a legat inca de Gantt/WBS sau de `SiteEquipmentPlan`/`SiteStaffPlan`. Nu
s-a construit un catalog separat de "tarife pe rol". Wiring-ul catre generatorul
de deviz s-a facut in sprintul urmator (vezi mai jos).

## Extindere: Deviz AI calculat din manopera/utilaje retetei (2026-07-20)

Continuare directa a sprintului anterior - `ProjectAiToolsController::
generateEstimate()` inca cerea manual "Cost manopera (RON/unitate)" si "Cost
utilaje (RON/unitate)", desi reteta are acum aceste date. Inlocuit integral cu
calcul automat, la fel ca la materiale.

- Manopera: `laborItems->hours = hours_per_unit * cantitate * factor
  complexitate`, `cost = hours * hourly_rate` (tariful e cel de pe randul
  retetei). Utilaje: la fel, dar tariful se citeste live din
  `Equipment.cost_per_hour` (nu de pe randul retetei).
- Daca o reteta nu are inca randuri de manopera/utilaje (retete vechi,
  neactualizate), costul respectiv e 0 - nu blocheaza generarea (spre
  deosebire de lipsa completa a retetei, care tot blocheaza).
- Raspunsul `generateEstimate()` capata `labor.lines`/`equipment.lines`
  (defalcare per rol/utilaj, ca la materiale) si un bloc nou `timing`
  (`execution_hours` = suma orelor de manopera, + `drying_hours`/
  `curing_hours` de pe reteta = `total_hours`) - primul pas concret spre
  "planificare cu timeline", fara sa construim inca integrarea cu Gantt.
- `Projects/Show.vue`: sters cele 2 inputuri manuale RON/unitate; adaugate
  liste de manopera/utilaje estimate (acelasi tipar ca materialele) si un
  card mic "Durata estimata" cand `total_hours > 0`.

### In afara scopului
Nu s-a extras inca un `RecipeCalculatorService` dedicat (logica ramane in
`ProjectAiToolsController`, ca si pana acum pentru materiale). Nu s-a legat
inca de Gantt/WBS - blocul de durata e doar informativ in rezultatul
devizului, nu influenteaza inca etapele generate.
