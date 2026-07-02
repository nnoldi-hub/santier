# Sprint 2 Audit si Plan

## 1) Rezumat audit tehnic

### Implementat solid
- Modul Clienti: CRUD complet (index/create/edit/show partial prin proiecte asociate)
- Modul Proiecte: CRUD complet + asociere client + statusuri
- Modul Etape proiect: add/edit/delete/progress
- Dashboard: statistici de baza si proiecte recente
- Auth + Profile: functional

### Gauri functionale (fata de plan)
- Lipsa modul Quotes/Devize (model, migration, UI, PDF)
- Lipsa notificari automate (evenimente + queue jobs)
- Lipsa rapoarte zilnice de santier

### Risc tehnic observat
- tenant_id este hardcodat la 1 in mai multe locuri (ok pentru MVP, dar risc la extindere)
- Fara politici de autorizare pe entitati (in afara auth simplu)
- Testare automata redusa pe fluxurile business

## 2) Sprint 2 propus (ordine recomandata)

Durata recomandata: 2 saptamani (10 zile lucratoare)
Capacitate estimata: 1-2 developeri full-time

### P1. Modul Task-uri minim viabil
Scop:
- task-uri pe proiect si optional pe etapa
- status: todo, in_progress, done, cancelled
- prioritate + termen limita + asignare user

Deliverables:
- migration tasks
- model Task + relatii
- TaskController + FormRequest
- pagini Inertia pentru list/create/edit
- card simplu in pagina proiectului

Estimare: 2 zile

### P2. Modul Echipe (MVP)
Scop:
- echipe pe specialitati
- membri echipa
- alocare echipa pe etapa

Deliverables:
- migrations teams, team_members, phase_team_assignments
- modele Eloquent + relatii
- CRUD echipe
- alocare din pagina proiectului
- validari supraalocare de baza (aceeasi echipa in acelasi interval)

Estimare: 2.5 zile

### P3. Modul Defecte/Snag List (MVP)
Scop:
- inregistrare defect pe proiect/etapa
- status workflow (open -> in_progress -> resolved)
- prioritate si responsabil

Deliverables:
- migrations defects, defect_photos
- model + controller + FormRequest
- UI lista defecte pe proiect
- update status rapid

Estimare: 2 zile

### P4. Dashboard v2
Scop:
- KPI utili pentru executie

Deliverables:
- widget: etape intarziate
- widget: progres mediu pe proiect
- widget: defecte deschise
- widget: task-uri restante azi/saptamana

Estimare: 1.5 zile

### P5. Notificari automate de baza
Scop:
- alerta pe reguli simple

Reguli recomandate:
- etapa depasita cu peste 2 zile
- task cu deadline depasit
- defect critic deschis peste X zile

Deliverables:
- events/listeners/jobs
- notificari in DB + badge in UI

Estimare: 1.5 zile

### P6. Hardening + QA
Scop:
- stabilizare pentru Sprint 3

Deliverables:
- policies pentru proiect/client/task/defect
- seederi pentru date demo
- teste feature pentru fluxurile critice
- checklist UAT

Estimare: 0.5-1 zi

## 3) Backlog tehnic (dupa Sprint 2)
- Quotes/Devize + PDF generator
- Gantt avansat (dependente, drag and drop)
- raportare zilnica + consum materiale
- audit log extins pe entitati business
- pregatire pentru multi-tenant real

## 4) Criterii de acceptanta Sprint 2
- User autentificat poate crea si urmari task-uri pe proiect
- Se pot defini echipe si aloca pe etape fara conflict simplu
- Se pot inregistra defecte si inchide cu status clar
- Dashboard afiseaza KPI actionabili, nu doar placeholder
- Exista minim 6-8 teste feature pe fluxurile noi

## 5) Stare actualizata 2026-07-02
Completat in cod si validat cu teste:
- Task management minim viabil: listare, filtrare si schimbare status
- Modul Echipe: listare filtrabila si alocari pe etape deja functionale
- Modul Defecte/Snag List: listare filtrabila si schimbare status
- Dashboard KPI avansat: etape intarziate, taskuri restante, defecte deschise, plan vs real
- Administrare IAM din tenant: utilizatori, roluri custom si filtre pe liste

Ramas deschis:
- Quotes/Devize + PDF generator
- Notificari automate pe reguli de executie
- Rapoarte zilnice de santier
