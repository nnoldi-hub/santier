# MODULIA - Stare curenta si plan de dezvoltare

Acest fisier este sursa de adevar pentru starea aplicatiei: ce e construit si functional,
si ce ramane de facut. Nu mai e jurnal cronologic (istoricul detaliat ramane in
`git log`/commit messages) - se actualizeaza pe loc cand un item de backlog e finalizat,
in loc sa se adauge intrari noi la infinit.

## 1. Ce este aplicatia

Modulia e o platforma SaaS multi-tenant pentru firme de constructii: fiecare firma
(tenant) isi gestioneaza proiectele de la etape de lucru (WBS) pana la rapoarte de
progres si control de costuri, cu izolare completa fata de alte firme. Deasupra
firmelor exista un nivel de superadmin care administreaza platforma (firme, abonamente,
branding implicit, pipeline comercial).

Stack: Laravel 11/12 + Inertia.js + Vue 3, MySQL, Tailwind CSS, deploy pe hosting shared
(Hostico/cPanel) prin `git pull` in `~/repositories/modulia-app`, cu `public_html`
configurat sa bootstrap-eze acea aplicatie.

## 2. Stare curenta pe domenii (verificat in cod, nu doar declarativ)

### 2.1. Proiecte si planificare - COMPLET
- Proiecte (CRUD, client, buget, status).
- WBS ierarhic (etape parinte + sub-etape), alocare contractor pe etapa.
- Gantt, Calendar echipe, Calendar utilaje, Calendar resurse - toate cu filtre pe
  interval (Azi/7 zile/30 zile).

### 2.2. Resurse - COMPLET
- Contractori/Subcontractori (+ agenda operationala zilnica), Echipe, Utilaje
  (rezervare pe etapa + calendar + cost estimat), Materiale.
- **Trasabilitate resurse** (comenzi -> livrari -> confirmari -> documente):
  fundatia de date si UI de baza exista si functioneaza (`resource_orders`,
  `resource_deliveries`, `resource_confirmations`, `resource_document_links`,
  pagina `ResourceOrders/Index` + `Show`), inclusiv reconciliere automata cu prag de
  toleranta configurabil si blocare automata la plata (`blocked_payment`) cand
  diferentele depasesc pragul, plus audit complet pe fiecare actiune. Logica de
  reconciliere e extrasa intr-un serviciu partajat
  (`App\Support\ResourceOrderReconciliation`) reutilizat de pagina de detaliu si de
  pagina agregata.
- **Trasabilitate materiale** (`/trasabilitate-materiale`): pagina dedicata, agrega
  pe fiecare material comandat/livrat/consumat + valoare comandata + facturi materiale
  (total/neplatit), cu status per material (conform/cu diferente/blocat la plata) rezultat
  din cea mai grava stare a comenzilor lui.
- **Trasabilitate utilaje** (`/trasabilitate-utilaje`, V1): pagina dedicata, agrega pe
  fiecare utilaj numarul de rezervari, total zile rezervate, cost estimat (formula unica
  `App\Support\EquipmentCostEstimator`, in loc de cele 3 formule inconsistente gasite
  anterior in cod), rezervari active azi. Fara flux de confirmare ore/cost final (schema
  `stage_equipment` nu are camp de confirmare - ar necesita migratie noua, ramas backlog
  daca se doreste varianta completa).

### 2.3. Financiar - COMPLET
- Documente financiare (contracte/facturi/devize/oferte) cu upload fisier si branding
  personalizat **per firma** (logo, culoare, date companie - izolat pe tenant).
- Facturi materiale (CRUD + analytics restante/expunere).
- Oferte/Devize, cu generare AI (`Deviz automat din dimensiuni`) si PDF profesional
  cu breakdown materiale/manopera.
- Cost tracking (buget vs oferte vs acceptat, top proiecte dupa abatere).

### 2.4. Calitate - COMPLET
- Verificari calitate, Defecte (Snag), Rapoarte calitate (alias peste Verificari),
  Procese verbale (alias peste Documente).

### 2.5. Raportare si executie - COMPLET
- Rapoarte de etapa (+ alias "Situatii de lucrari"), Taskuri generale, Taskuri pe etapa
  (asignare user/echipa/contractor), Progres etape (dashboard dedicat cu risc
  operational).

### 2.6. AI Tools (in pagina proiectului) - COMPLET
- Card 1: Factura prin poza -> extractie OCR -> revizuire -> creare Document +
  Contractor.
- Card 2: Deviz automat din dimensiuni -> materiale/manopera/utilaje + etape WBS
  propuse -> commit in oferta + proiect.
- Card 3: Alerta depasire buget -> impact profit + recomandare.

### 2.7. Dashboard - COMPLET
- KPI operationale si financiare, Calendar operational azi (6 categorii, click-to-navigate),
  predictii AI (risc intarziere/buget/subcontractor) cu explicabilitate (popover cu
  factori si ponderi), Plan vs Real pe etape.

### 2.8. Exporturi enterprise - COMPLET pentru functiile de baza, PARTIAL pentru "premium"
- Toate modulele exportabile CSV/XLSX/PDF, cu template-uri one-click (Proiect complet,
  Financiar complet, Calitate&Defecte, Utilaje&Resurse, Taskuri&Progres, Cost vs Buget,
  Materiale&Avize), cautare globala + intervale rapide, preview simplu (numar
  inregistrari + esantion), audit complet pe fiecare export, distribuire automata pe
  email (abonari programate), raport managerial PDF, raport comparativ
  `Materiale & Avize` (comandat/livrat/receptionat/consumat/diferente).
- **Lipsesc** din planul "Exporturi Premium v2": Favorite Reports + Saved Filters
  (salvare filtre/rapoarte preferate per user), preview cu grafice (doar tabel/sumar
  text acum), raport managerial "avansat" multi-sectiune cu grafice + generator pe
  interval (saptamanal/lunar/trimestrial/anual) cu abonare, si inca 2 din cele 3
  rapoarte comparative Materiale&Avize planificate (Trasabilitate materiale completa,
  Utilaje & consum materiale) - vezi backlog.

### 2.9. Cont si organizatie (IAM) - COMPLET
- Superadmin (platforma) vs tenant admin (per firma), izolare completa verificata cu
  teste dedicate (fara IDOR cross-tenant), roluri/permisiuni (Spatie), invitatii cu
  email (link de setare parola), audit acces, actiuni Suspenda/Reactiveaza/Reinvita/
  Elimina din firma pe utilizatori.

### 2.10. Comercial - PARTIAL
- Firme pilot: pipeline complet (invitat -> contactat -> demo -> trial -> rezultat),
  cu taskuri comerciale automate + notificari la schimbare de etapa/status.
- Dashboard Comercial si Firme & Abonamente (ambele in zona de superadmin), aliniate
  vizual la brand.
- **Lipsesc**: log de actiuni comerciale (`commercial_actions` - istoric apel/email/
  demo/negociere per lead, momentan doar taskurile automate exista) si inbox-ul
  comercial din dashboard (widget "Taskuri azi" / "follow-up restante" / "oportunitati
  stagnante") - vezi backlog.

### 2.11. Admin platforma (superadmin) - COMPLET
- Administrare (setari globale: branding platforma, trial, feature flags), Firme &
  Abonamente, Dashboard Comercial, Firme pilot - toate 4 pagini modernizate vizual si
  aliniate la paleta de brand (`#F57C00` portocaliu, `#1A237E` navy).

### 2.12. Autentificare - COMPLET
- Login/Register/Reset parola/Verificare email/Confirmare parola - toate rebranduite
  Modulia (romana, paleta de brand).

### 2.13. Proiect demo public - COMPLET
- `php artisan db:seed --class=PublicDemoSeeder` - cont `demo@santier.local` /
  `Demo1234!`, proiect `Renovare Office Park - Corp A`, date suficiente pentru evaluare
  end-to-end pe toate modulele.

## 3. Backlog - ce ramane de facut

Doar itemi din initiative deja pornite (nu propuneri noi). Ordinea nu implica prioritate.

1. ~~Trasabilitate materiale (pagina dedicata)~~ - FACUT (`/trasabilitate-materiale`,
   vezi 2.2). Ramas explicit in afara scopului: legarea `MaterialInvoice` de
   `resource_orders`/`resource_document_links` (sunt doua sisteme de facturare separate,
   neconectate in schema - aratate distinct, nu unificate).
2. ~~Trasabilitate utilaje (pagina dedicata) - V1~~ - FACUT (`/trasabilitate-utilaje`,
   vezi 2.2). Ramas explicit in afara scopului (backlog separat daca se doreste): flux
   de confirmare ore/cost final aprobat - ar necesita o migratie noua pe
   `stage_equipment` (varianta V2, discutata si respinsa pentru aceasta runda).
3. **Comercial - log de actiuni** (`commercial_actions`): istoric apel/email/demo/oferta/
   follow-up/negociere per lead, separat de taskurile automate existente.
4. **Comercial - inbox/widget dashboard** (C3): "Taskuri azi", "follow-up restante",
   "oportunitati stagnante", "handoff-uri catre onboarding" + filtre in
   `PilotInvites/Index` (doar cu reminder azi / fara next step / stagnante).
5. **Exporturi - Favorite Reports + Saved Filters**: tabele noi `report_favorites` si
   `saved_export_filters`, tab dedicat in pagina de exporturi.
6. **Exporturi - preview cu grafice**: extindere endpoint preview cu structura
   `charts` (labels+series) pentru rapoartele manageriale, in loc de doar tabel/sumar.
7. **Exporturi - raport managerial avansat + raport pe interval**: PDF multi-sectiune
   cu grafice, generator saptamanal/lunar/trimestrial/anual cu abonare automata.
8. **Exporturi - completare pachet Materiale & Avize**: mai lipsesc 2 din cele 3
   rapoarte comparative planificate (doar `resource-comparison` exista azi):
   - Trasabilitate materiale completa (timeline documente + actori, vezi si #1).
   - Utilaje & consum materiale (consum pe utilaj - pompa/macara/buldo etc. - corelat
     cu etapa).
9. **Emoji reziduale** in `Projects/Show.vue` (~21 aparitii) si `Welcome.vue` (~18
   aparitii) - scoase explicit din scope-ul rondelor de modernizare vizuala anterioare,
   raman ca polish minor daca se doreste.
10. **Verificare operationala**: confirmarea ca `php artisan iam:backfill-legacy-roles
    --apply` a rulat pe productie (necesar dupa eliminarea bypass-ului legacy de
    autorizare) - de verificat daca nu s-a facut deja.

## 4. Note tehnice utile pentru viitor

- **Verificare `git status` la inceput de sesiune**: la un moment dat, 4 relatii Eloquent
  (`resourceOrders()` pe `Material`, `Equipment`, `Project`, `ProjectPhase`) au stat
  necomise local mult timp, desi jurnalul vechi le pretindea livrate - nimeni nu le-a
  observat pana cand `Material::resourceOrders()` a cauzat un 500 in productie (folosit
  de `MaterialTraceabilityController`). Lectie: fisiere modificate persistent in
  `git status` la inceputul mai multor sesiuni merita verificate explicit (sunt scratch
  ale userului sau cod real neterminat?), nu doar excluse automat din commituri.
- **Multi-tenancy**: `AppSetting` (setari cheie-valoare) e acum scopat pe `tenant_id`
  (`0` = platforma globala). Orice setare noua per-firma trebuie sa foloseasca
  `AppSetting::allForTenant()`/`setValues(..., $tenantId)`, nu varianta fara tenant.
- **Servire fisiere din storage**: NU se mai foloseste simlink `public/storage` ca
  dependinta - Laravel serveste nativ `/storage/{path}` pentru discul cu
  `'serve' => true` in `config/filesystems.php` (in prezent discul `public`). Daca se
  adauga un disc nou ce trebuie servit public, seteaza `serve` + `visibility: public`
  pe acel disc, nu pe discul `local` (privat).
- **PDF-uri cu logo/branding**: `App\Support\DocumentBranding::resolveLogoPath()`
  transforma un `document_logo_url` relativ intr-o cale reala pe disc pentru dompdf -
  orice view PDF nou care afiseaza logo trebuie sa treaca branding-ul prin acest helper
  inainte de randare.
- **Deploy productie**: `git pull` in `~/repositories/modulia-app`, apoi
  `composer dump-autoload` (daca s-au adaugat clase noi), `php artisan optimize:clear`,
  `php artisan migrate --force` (daca sunt migratii noi). PHP-ul de pe acest hosting NU
  are `exec()` activat (storage:link prin artisan nu functioneaza - simlink-uri se
  creaza manual din shell cu `ln -s` daca sunt necesare).
