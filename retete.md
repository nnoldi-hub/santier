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
