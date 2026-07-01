# SANTIER — Plan de Dezvoltare Robust & Scalabil
> ERP pentru managementul lucrărilor de construcții/renovări

---

## 1. VIZIUNE & OBIECTIVE

**Produsul:** Platformă web SaaS multi-tenant pentru antreprenori de construcții, care centralizează ofertarea, planificarea, echipele și urmărirea șantierului.

**Utilizatori țintă:**
- Antreprenor general / patron firmă
- Șef de șantier
- Devizier / economist
- Muncitor (acces limitat — raportare progres)

**Obiective non-funcționale:**
- Timp de răspuns API < 200ms pentru operații uzuale
- Disponibilitate 99.5% (single-tenant inițial, SaaS ulterior)
- Suport offline parțial pe mobil (PWA)
- Export/import date fără vendor lock-in

---

## 2. STACK TEHNOLOGIC

### Backend
| Componentă | Tehnologie | Justificare |
|---|---|---|
| Framework | **Laravel 11** | Ecosistem matur, Eloquent ORM, Queue, Events |
| Bază de date | **PostgreSQL 16** | JSON columns, full-text search, fiabil |
| Cache | **Redis 7** | Sessions, queue, cache layer |
| Queue | **Laravel Horizon** (Redis) | Job-uri async: PDF, notificări |
| Search | **Laravel Scout + Meilisearch** | Căutare rapidă proiecte/materiale |
| Storage | **S3 / MinIO** | Documente, PDF-uri, poze șantier |
| Auth | **Laravel Sanctum** (API tokens) | SPA + Mobile |

### Frontend
| Componentă | Tehnologie | Justificare |
|---|---|---|
| Framework | **Inertia.js + Vue 3** | SPA fără API separată, SSR opțional |
| UI Library | **PrimeVue** sau **shadcn-vue** | Componente profesionale |
| State | **Pinia** | State management simplu |
| Grafice | **ApexCharts** | Dashboard, Gantt, burndown |
| Gantt | **Frappe Gantt** (default) sau **Bryntum Gantt** (premium) | Planificare etape |
| PDF Preview | **PDFjs** | Vizualizare oferte |
| PWA | **Vite PWA Plugin** | Acces mobil offline |

### DevOps & Infrastructură
| Componentă | Tehnologie |
|---|---|
| Containerizare | Docker + Docker Compose |
| CI/CD | GitHub Actions |
| Hosting | VPS (Hetzner/DigitalOcean) sau Laravel Forge |
| Reverse Proxy | Nginx + SSL (Let's Encrypt) |
| Monitorizare | Laravel Telescope (dev) + Sentry (prod) |
| Backup | pg_dump automatizat zilnic → S3 |

---

## 3. ARHITECTURA APLICAȚIEI

```
┌─────────────────────────────────────────────────────┐
│                    FRONTEND (SPA)                    │
│              Vue 3 + Inertia.js + Pinia             │
└──────────────────────┬──────────────────────────────┘
                       │ HTTP/WebSocket
┌──────────────────────▼──────────────────────────────┐
│                 LARAVEL APPLICATION                  │
│  ┌────────────┐ ┌──────────────┐ ┌───────────────┐  │
│  │  HTTP API  │ │  WebSockets  │ │  Queue/Jobs   │  │
│  │ (Inertia)  │ │  (Reverb)    │ │  (Horizon)    │  │
│  └─────┬──────┘ └──────┬───────┘ └───────┬───────┘  │
│        │               │                  │           │
│  ┌─────▼───────────────▼──────────────────▼───────┐  │
│  │              SERVICE LAYER                      │  │
│  │  QuoteService │ GanttService │ TeamService      │  │
│  │  ReportService │ NotificationService            │  │
│  └─────────────────────┬───────────────────────────┘  │
└────────────────────────┼────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
    ┌────▼────┐    ┌─────▼────┐   ┌─────▼────┐
    │PostgreSQL│    │  Redis   │   │  S3/MinIO│
    └─────────┘    └──────────┘   └──────────┘
```

### Pattern-uri arhitecturale
- **Repository Pattern** — abstractizare acces date
- **Service Layer** — logică business separată de controllere
- **Action Classes** — operații atomice (CreateProject, GenerateQuotePDF)
- **Events & Listeners** — decuplare notificări, audit log
- **Policy-based Authorization** — Gates & Policies Laravel
- **Multi-tenancy** — Faza 1–2: `tenant_id` pe fiecare tabel (simplu, suficient); Faza 3: Stancl/tenancy pentru izolare completă dacă > 50 firme active

---

## 4. SCHEMA BAZEI DE DATE

### Entități principale

```sql
-- TENANTS (multi-tenancy pregătit)
tenants: id, name, slug, plan, settings(json), created_at

-- UTILIZATORI
users: id, tenant_id*, name, email, password, phone, role(enum), avatar, active

-- PROIECTE
projects: id, tenant_id, client_id, name, description, address,
          status(enum: draft|active|paused|completed|cancelled),
          start_date, end_date, total_budget, notes, created_by

-- CLIENȚI
clients: id, tenant_id, name, type(person|company), tax_id, address,
         email, phone, contact_person

-- ETAPE PROIECT (Gantt)
project_phases: id, project_id, name, type(enum), order, 
                start_date, end_date, duration_days, 
                status(enum: pending|in_progress|completed|blocked),
                depends_on(json array of phase_ids), progress_pct,
                notes

-- ECHIPE
teams: id, tenant_id, name, specialty(enum), leader_id(->users)

-- MEMBRII ECHIPĂ
team_members: id, team_id, user_id, role, hourly_rate, joined_at, left_at

-- ALOCARE ECHIPE PE ETAPE
phase_team_assignments: id, phase_id, team_id, workers_needed,
                        workers_assigned, start_date, end_date, notes

-- CATALOG MATERIALE
materials: id, tenant_id, code, name, category, unit(m²|ml|buc|kg|mc),
           unit_price, supplier, notes

-- OFERTE (DEVIZE)
quotes: id, project_id, version, title, status(enum: draft|sent|accepted|rejected),
        valid_until, discount_pct, tva_pct, notes, 
        total_materials, total_labor, total_net, total_tva, total_gross,
        sent_at, accepted_at, created_by

-- LINII DEVIZ
quote_items: id, quote_id, phase_id*, material_id*, type(material|labor|other),
             description, quantity, unit, unit_price, labor_hours,
             labor_rate, total_price, order

-- ȘABLOANE LUCRĂRI
work_templates: id, tenant_id, name, phase_type, description
work_template_items: id, template_id, material_id*, description, 
                     qty_formula, unit, labor_hours_formula

-- RAPORTARE PROGRES ZILNIC
daily_reports: id, phase_id, reported_by, report_date, progress_delta_pct,
               workers_present, hours_worked, notes, weather

-- CONSUM MATERIALE (tracker real vs estimat)
material_consumptions: id, project_id, phase_id, material_id, 
                       quantity_used, unit_price_actual, reported_by, consumed_at

-- NOTIFICĂRI
notifications: id, tenant_id, user_id, type, title, body, 
               data(json), read_at, created_at

-- AUDIT LOG
activity_logs: id, tenant_id, user_id, event, subject_type, subject_id,
               properties(json), ip_address, created_at

-- DOCUMENTE / FIȘIERE
documents: id, tenant_id, subject_type, subject_id, name, 
           path, mime_type, size, uploaded_by

-- TASKURI
tasks: id, project_id, phase_id*, assigned_to(->users), created_by,
       title, description, status(enum: todo|in_progress|done|cancelled),
       priority(enum: low|medium|high), deadline, completed_at

-- DEFECTE / SNAG LIST
defects: id, project_id, phase_id*, reported_by, assigned_to*,
         title, description, location, status(enum: open|in_progress|resolved|rejected),
         priority(enum: low|medium|high), due_date, resolved_at
defect_photos: id, defect_id, path, uploaded_by, created_at

-- CALENDAR SYNC
calendar_integrations: id, user_id, provider(enum: google|outlook),
                       access_token(encrypted), refresh_token(encrypted),
                       token_expires_at, calendar_id, sync_enabled, last_synced_at
calendar_events: id, tenant_id, subject_type(phase|task), subject_id,
                 provider_event_id, synced_at
```

---

## 5. MODULE FUNCȚIONALE — DETALIERE

### Modulul 1: OFERTARE (Quotes)

**Features:**
- Creare deviz din zero sau din șablon
- Adăugare materiale din catalog + introduse manual
- Calcul automat manoperă (ore × tarif)
- Versioning oferte (v1, v2, v3...)
- Generare PDF profesional cu logo firmă
- Trimitere PDF pe email direct din aplicație
- Acceptare/respingere ofertă cu tracking
- Comparare versiuni deviz
- Import prețuri materiale din Excel/CSV

**Calcule automate:**
```
Total linie = cantitate × preț_unitar
Total manoperă = ore_manoperă × tarif_orar
Total net = Σ(linii materiale) + Σ(linii manoperă)
Total TVA = Total net × tva_pct / 100
Total brut = Total net + Total TVA
```

**Șabloane:** Set predefinit pentru tipuri comune:
- Renovare completă apartament
- Zugrăveli interioare
- Montaj gresie/faianță
- Izolație termică
- Instalații electrice / sanitare

---

### Modulul 2: PLANIFICARE (Gantt)

**Alegerea bibliotecii Gantt:**
| Opțiune | Preț | Integrare Vue | Când să alegi |
|---|---|---|---|
| **Frappe Gantt** | Gratuit | Wrapper simplu | Start rapid, funcții de bază |
| **DHTMLX Gantt** | Freemium | Medie | Funcții medii, licență la scale |
| **Syncfusion Gantt** | ~$0 (community) | Componentă nativă | Echilibru preț/funcții |
| **Bryntum Gantt** | Premium | Nativă Vue | Cel mai robust, producție serioasă |

> **Recomandare:** Faza 1 → **Frappe Gantt** (rapid, gratuit). Faza 2+ → migrare la **Bryntum** dacă clienții cer funcții avansate.

**Features:**
- Creare etape cu tip predefinit sau custom
- Setare dependențe (finish-to-start, start-to-start)
- Vizualizare Gantt interactivă (drag & drop durate)
- Avertizare conflicte / suprapuneri
- Calcul dată finalizare estimată
- Replanificare automată la schimbare dependențe
- Milestone-uri (puncte cheie)
- Export Gantt ca PNG/PDF

**Tipuri etape predefinite:**
`DEMOLARE` → `STRUCTURA` → `INSTALATII_BRUTE` → `TENCUIELI` → 
`SAPE` → `GLET` → `FINISAJE_UMEDE` → `MONTAJ_TAMPLARIE` → 
`ZUGRAVELI` → `PARDOSELI` → `FINISAJE_FINE` → `CURATENIE`

---

### Modulul 3: MANAGEMENT ECHIPE

**Features:**
- Registru angajați cu tarife orare
- Creare echipe pe specialitate
- Alocare echipă pe etapă de proiect
- Calendar disponibilitate echipă
- Detectare supraalocare (echipă pe 2 proiecte simultan)
- Estimare automată durată etapă în funcție de echipă
- Pontaj simplu (check-in/check-out sau ore/zi)
- Calcul cost manoperă real vs estimat

**Formula estimare durată:**
```
durata_zile = volum_lucrare / (nr_muncitori × productivitate_zilnica)
```

---

### Modulul 4: DASHBOARD ȘANTIER

**Widgets:**
- Progres general proiect (%)
- Progres per etapă (bare progres colorate)
- Gantt mini-view (ultimele 30 zile)
- Costuri: estimat vs real (grafic)
- Materiale: consum estimat vs real
- Echipe active azi
- Etape cu întârzieri (highlight roșu)
- Notificări nerezolvate
- Rapoarte zilnice recente
- **Timeline întârzieri** — vizualizare cronologică a tuturor deviațiilor de la plan
- **Heatmap echipe** — calendar vizual cine lucrează unde și când (detectare supraalocare vizual)
- **Predicție finalizare proiect** — calcul weighted progress: `data_estimata = azi + (zile_ramase / viteza_medie_progres)`

**Alerting automat:**
- Etapă depășită cu > 2 zile → notificare șef șantier
- Buget depășit cu > 10% → notificare patron
- Consum material > 120% din estimat → alertă
- Etapă fără raport zilnic de > 1 zi lucrătoare

---

### Modulul 5: TASKING

**Scop:** Sarcini operative rapide pentru șefii de șantier.

**Features:**
- Creare task cu titlu, descriere, responsabil, deadline, prioritate
- Statusuri simple: `Todo → In Progress → Done`
- Filtrare per proiect / etapă / responsabil
- Notificare automată la atribuire și cu 1 zi înainte de deadline
- Widget „Taskuri azi" în dashboard
- Task opțional legat de o etapă Gantt

**Flow tipic:**
```
Șef șantier → crează task „Verifică cofrajele bloc A"
            → atribuie muncitorului X, deadline: mâine
            → X primește notificare → marchează Done
```

---

### Modulul 6: DEFECTE & REMEDIERI (Snag List)

**Scop:** Înregistrarea și urmărirea defectelor la recepție sau pe parcursul execuției.

**Features:**
- Adăugare defect: titlu, descriere, locație, prioritate
- Atașare poze direct din telefon (mobil-first)
- Atribuire responsabil remediere + termen
- Statusuri: `Open → In Progress → Resolved → Rejected`
- Raport PDF snag list pentru recepție oficială
- Notificare la atribuire și la rezolvare
- Widget dashboard: număr defecte deschise per proiect (badge roșu)

---

### Modulul 7: CALENDAR INTEGRATION (Google / Outlook)

**Scop:** Sincronizare etape și taskuri cu calendarul personal al utilizatorului.

**Features:**
- OAuth2 connect cu Google Calendar și Microsoft Outlook (Graph API)
- Sync automat etape proiect → evenimente calendar (creare/update/ștergere)
- Sync taskuri cu deadline → reminder calendar
- Selector calendar destinație per utilizator
- Revocare acces din setările contului
- Token-uri stocate **criptat** (`$casts = ['access_token' => 'encrypted']`)

**Implementare:**
```
Provider: Laravel Socialite + socialiteproviders/microsoft-graph
Job async: SyncPhaseToCalendarJob + SyncTaskToCalendarJob (queue: calendar)
Webhook: Google Push Notifications / MS Graph subscriptions
```

> **Securitate:** `access_token` și `refresh_token` niciodată loggate sau expuse în răspunsuri API.

---

## 6. ROLURI & PERMISIUNI

| Acțiune | Admin | Manager | Sef Santier | Devizier | Muncitor |
|---|---|---|---|---|---|
| Creare proiect | ✅ | ✅ | ❌ | ❌ | ❌ |
| Editare ofertă | ✅ | ✅ | ❌ | ✅ | ❌ |
| Trimitere ofertă | ✅ | ✅ | ❌ | ❌ | ❌ |
| Gestionare echipe | ✅ | ✅ | ✅ | ❌ | ❌ |
| Raport progres | ✅ | ✅ | ✅ | ❌ | ✅ |
| Vizualizare dashboard | ✅ | ✅ | ✅ | ✅ | ❌ |
| Creare/editare taskuri | ✅ | ✅ | ✅ | ❌ | ❌ |
| Marcare task Done | ✅ | ✅ | ✅ | ❌ | ✅ |
| Raportare defect | ✅ | ✅ | ✅ | ❌ | ✅ |
| Rezolvare defect | ✅ | ✅ | ✅ | ❌ | ✅ |
| Conectare calendar | ✅ | ✅ | ✅ | ✅ | ❌ |
| Setări firmă | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 7. FAZE DE DEZVOLTARE

### FAZA 0 — Setup & Infrastructură (Săptămâna 1)
- [x] Inițializare proiect Laravel 13 + Inertia v3 + Vue 3 ✅ *2026-06-25*
- [x] Configurare MySQL (Laragon) + DB `santier` creat ✅ *2026-06-25*
- [x] Instalare Spatie Permission + ActivityLog + Ziggy + Pinia ✅ *2026-06-25*
- [x] Configurare Vite + @vitejs/plugin-vue + build funcțional ✅ *2026-06-25*
- [x] Blade root `app.blade.php` + prima pagină `Welcome.vue` ✅ *2026-06-25*
- [x] Migrații inițiale rulate (users, cache, jobs, permissions, activity_log) ✅ *2026-06-25*
- [ ] Configurare GitHub repo + branch strategy (main/develop/feature/*)
- [ ] GitHub Actions: lint + test automat la PR
- [ ] Configurare Laravel Telescope
- [ ] Configurare Sentry (error tracking)
- [ ] Seeders cu date demo

---

### FAZA 1 — MVP Core (Săptămânile 2–7)

**Sprint 1 (S2–S3): Auth & Proiecte**
- [ ] Autentificare (email/parolă) cu remember me
- [ ] Management utilizatori + roluri (Spatie Permission)
- [ ] CRUD Proiecte cu client
- [ ] CRUD Clienți
- [ ] Dashboard proiect (placeholder)
- [ ] Navigație + layout principal

**Sprint 2 (S4–S5): Etape & Echipe**
- [ ] CRUD Etape proiect
- [ ] Dependențe între etape
- [ ] Vizualizare Gantt (DHTMLX)
- [ ] CRUD Echipe + Angajați
- [ ] Alocare echipă pe etapă

**Sprint 3 (S6–S7): Ofertare Basic**
- [ ] Catalog materiale
- [ ] Creare deviz cu linii
- [ ] Calcul automat totale
- [ ] Generare PDF (**Browsershot/Puppeteer** — PDF-uri profesionale cu CSS complet; DomPDF ca fallback lightweight)
- [ ] Dashboard progres basic

---

### FAZA 2 — Funcții Avansate (Săptămânile 8–14)

**Sprint 4 (S8–S9): Ofertare Avansată**
- [ ] Șabloane lucrări cu formule
- [ ] Versioning devize
- [ ] Import materiale din Excel (Maatwebsite/Excel)
- [ ] Trimitere PDF pe email (Mailable + Queue)
- [ ] Acceptare/respingere ofertă cu semnătură digitală simplă

**Sprint 5 (S10–S11): Tracking Real + Tasking + Defecte**
- [ ] Rapoarte zilnice progres
- [ ] Consum materiale (real vs estimat)
- [ ] Pontaj echipe (ore lucrate/zi)
- [ ] Calcul cost real manoperă
- [ ] Modul Tasking (CRUD taskuri, atribuire, notificări deadline)
- [ ] Modul Defecte / Snag List (CRUD, upload poze, statusuri)
- [ ] Raport PDF snag list

**Sprint 6 (S12–S13): Notificări, Dashboard Complet & Calendar**
- [ ] Sistem notificări (DB + email)
- [ ] Alerting automat (întârzieri, depășiri buget)
- [ ] Dashboard cu toate widget-urile (incl. taskuri azi + defecte deschise)
- [ ] Grafice costuri (ApexCharts)
- [ ] Export raport proiect PDF
- [ ] OAuth2 Google Calendar + Microsoft Outlook (Socialite)
- [ ] Job async SyncPhaseToCalendarJob + SyncTaskToCalendarJob
- [ ] Setări utilizator: conectare/deconectare calendar, selector calendar

**Sprint 7 (S14): Calitate & Polish**
- [ ] Teste Feature (PHPUnit) — minim 70% coverage
- [ ] Teste E2E (Playwright) — fluxuri critice
- [ ] Optimizare queries (Laravel Debugbar)
- [ ] Audit securitate OWASP (XSS, CSRF, Mass assignment)

---

### FAZA 3 — Automatizări & Scale (Săptămânile 15–20)

**Sprint 8: Smart Features**
- [ ] Estimare automată durate din șabloane
- [ ] Sugestii materiale bazate pe tipul lucrării
- [ ] Import/Export complet Excel
- [ ] Replanificare automată la schimbare etapă
- [ ] Calculatoare specializate (suprafețe, volume, necesare)

**Sprint 9: Mobile & Offline**
- [ ] PWA cu Service Worker
- [ ] Offline: raportare progres + consum materiale
- [ ] Sync când reintri online (background sync)
- [ ] Notificări push (web push)

**Sprint 10: Multi-tenancy & SaaS**
- [ ] Izolare date per tenant (Stancl/tenancy)
- [ ] Planuri de subscripție (Free/Pro/Business)
- [ ] Onboarding wizard
- [ ] Billing integrat (Stripe via Laravel Cashier)
- [ ] Admin panel super-admin

---

### FAZA 4 — Optimizare & Integrări (Post-lansare)
- [ ] API REST publică (pentru integrări terțe)
- [ ] Integrare contabilitate (Saga/SmartBill)
- [ ] App mobilă nativă (Flutter sau React Native)
- [ ] AI: estimare costuri din poze șantier
- [ ] Marketplace șabloane lucrări

---

## 8. STRUCTURA PROIECTULUI LARAVEL

```
app/
├── Actions/               # Operații atomice business
│   ├── Quotes/
│   │   ├── CreateQuote.php
│   │   ├── GenerateQuotePDF.php
│   │   └── SendQuoteByEmail.php
│   ├── Projects/
│   └── Reports/
├── Events/                # Domain events
├── Listeners/             # Handlers pentru events
├── Http/
│   ├── Controllers/
│   │   ├── ProjectController.php
│   │   ├── QuoteController.php
│   │   ├── PhaseController.php
│   │   ├── TeamController.php
│   │   └── DashboardController.php
│   ├── Middleware/
│   └── Requests/          # Form Request Validation
├── Models/
│   ├── Project.php
│   ├── Quote.php
│   ├── Phase.php
│   ├── Team.php
│   └── ...
├── Policies/              # Authorization
├── Repositories/          # Data access layer
├── Services/
│   ├── GanttService.php
│   ├── CostCalculator.php
│   ├── NotificationService.php
│   └── PDFGeneratorService.php
├── Jobs/                  # Queue jobs
│   ├── GeneratePDFJob.php
│   └── SendNotificationJob.php
└── Notifications/

resources/
├── js/
│   ├── Pages/             # Inertia pages (Vue)
│   │   ├── Projects/
│   │   ├── Quotes/
│   │   ├── Gantt/
│   │   └── Dashboard/
│   ├── Components/        # Componente reutilizabile
│   │   ├── Gantt/
│   │   ├── Charts/
│   │   └── Forms/
│   └── Stores/            # Pinia stores
└── views/
    └── pdf/               # Blade templates PDF
```

---

## 9. API ENDPOINTS (Inertia Routes)

```
// Auth
POST   /login
POST   /logout
GET    /dashboard

// Proiecte
GET    /projects
POST   /projects
GET    /projects/{id}
PUT    /projects/{id}
DELETE /projects/{id}

// Etape (Gantt)
GET    /projects/{id}/phases
POST   /projects/{id}/phases
PUT    /projects/{id}/phases/{phase}
DELETE /projects/{id}/phases/{phase}
PUT    /projects/{id}/phases/reorder
POST   /projects/{id}/phases/{phase}/complete

// Oferte
GET    /projects/{id}/quotes
POST   /projects/{id}/quotes
GET    /quotes/{id}
PUT    /quotes/{id}
POST   /quotes/{id}/send
POST   /quotes/{id}/duplicate
GET    /quotes/{id}/pdf

// Echipe
GET    /teams
POST   /teams
GET    /teams/{id}
PUT    /teams/{id}
POST   /teams/{id}/members

// Rapoarte
POST   /phases/{id}/daily-report
GET    /projects/{id}/reports

// Taskuri
GET    /projects/{id}/tasks
POST   /projects/{id}/tasks
PUT    /tasks/{id}
DELETE /tasks/{id}
PATCH  /tasks/{id}/status

// Defecte (Snag List)
GET    /projects/{id}/defects
POST   /projects/{id}/defects
GET    /defects/{id}
PUT    /defects/{id}
PATCH  /defects/{id}/status
POST   /defects/{id}/photos
GET    /projects/{id}/defects/pdf

// Calendar Integration
GET    /calendar/connect/{provider}
GET    /calendar/callback/{provider}
DELETE /calendar/disconnect/{provider}
POST   /calendar/sync

// Dashboard
GET    /projects/{id}/dashboard
GET    /projects/{id}/stats
```

---

## 10. SECURITATE

### Implementări obligatorii
- [ ] **CSRF Protection** — Laravel default, activ pe toate formularele
- [ ] **Rate Limiting** — `throttle:60,1` pe login, `throttle:30,1` pe API
- [ ] **Mass Assignment Protection** — `$fillable` explicit pe toate modelele
- [ ] **SQL Injection** — Eloquent ORM exclusiv, niciodată raw queries cu user input
- [ ] **XSS** — Inertia sanitizează automat; niciodată `v-html` cu date nesanitizate
- [ ] **Authorization** — Policy check pe fiecare acțiune, niciodată trust client
- [ ] **File Upload** — Validare MIME type server-side, stocare în afara `public/`
- [ ] **Secrets** — Niciodată în cod, mereu în `.env` (git ignored)
- [ ] **HTTPS** — Obligatoriu în producție, HSTS header
- [ ] **Audit Log** — Toate operațiile sensibile loggate cu user + IP

---

## 11. TESTARE

### Strategie
```
Unit Tests (Models, Services, Calculators)   ~30%
Feature Tests (HTTP, DB, Auth flows)         ~50%  
Browser/E2E Tests (Playwright)               ~20%
```

### Fluxuri E2E critice de testat
1. Login → Creare proiect → Adăugare etape → Alocare echipă
2. Creare deviz din șablon → PDF → Trimitere email
3. Raportare progres → Actualizare dashboard
4. Detectare întârziere → Generare notificare

---

## 12. METRICI & MONITORIZARE

### KPIs tehnici
- **Response time P99** < 500ms
- **Error rate** < 0.1%
- **Queue lag** < 30s (jobs PDF/email)
- **DB query time P95** < 50ms

### Unelte
- **Sentry** — Exception tracking cu alertare Slack
- **Laravel Pulse** (Laravel 11) — Metrici aplicație real-time
- **pganalyze** sau **pg_stat_statements** — Query performance
- **UptimeRobot** — Monitoring disponibilitate (gratuit)

---

## 13. PLAN LANSARE

### Checklist Pre-lansare
- [ ] Audit securitate complet
- [ ] Load testing (k6 sau Locust) — 50 users simultan
- [ ] Backup strategy testată + restore verificat
- [ ] GDPR: politică confidențialitate, ștergere date la cerere
- [ ] Documentație utilizator (Notion sau GitBook)
- [ ] Email-uri tranzacționale configurate (Mailgun/Postmark)

### Strategie lansare
1. **Alpha** (intern) — Faza 1 completă, 1-2 șantiere pilot
2. **Beta** (invitație) — 10 firme, colectare feedback
3. **Public** — Landing page, pricing, free trial 30 zile

---

## 14. ESTIMARE EFORT & COSTURI

### Timp dezvoltare (solo developer, 6h/zi)
| Fază | Durată | Efort |
|---|---|---|
| Faza 0 (Setup) | 1 săptămână | ~30h |
| Faza 1 (MVP) | 6 săptămâni | ~180h |
| Faza 2 (Avansat) | 7 săptămâni | ~210h |
| Faza 3 (Scale) | 6 săptămâni | ~180h |
| **Total** | **~20 săptămâni** | **~600h** |

### Costuri infrastructură/lună (la lansare)
| Serviciu | Cost estimat |
|---|---|
| VPS (4 core, 8GB RAM) | ~30$/lună |
| PostgreSQL managed (opțional) | ~25$/lună |
| S3/MinIO stocare | ~5$/lună |
| Email (Mailgun/Postmark) | ~10$/lună |
| Sentry (free tier) | 0$/lună |
| **Total** | **~70$/lună** |

---

## 15. DEPENDENȚE COMPOSER (KEY PACKAGES)

```json
{
  "require": {
    "laravel/framework": "^11.0",
    "inertiajs/inertia-laravel": "^1.0",
    "tightenco/ziggy": "^2.0",
    "spatie/laravel-permission": "^6.0",
    "spatie/laravel-activitylog": "^4.0",
    "spatie/laravel-media-library": "^11.0",
    "barryvdh/laravel-dompdf": "^2.0",
    "spatie/browsershot": "^4.0",
    "maatwebsite/excel": "^3.1",
    "laravel/horizon": "^5.0",
    "laravel/scout": "^10.0",
    "laravel/reverb": "^1.0",
    "laravel/cashier": "^15.0",
    "laravel/socialite": "^5.0",
    "socialiteproviders/microsoft-graph": "^4.0"
  },
  "require-dev": {
    "laravel/telescope": "^5.0",
    "laravel/pint": "^1.0",
    "pestphp/pest": "^2.0",
    "pestphp/pest-plugin-laravel": "^2.0"
  }
}
```

---

## 16. NEXT STEPS IMEDIATE

1. **Azi** — Inițializare repo GitHub + `laravel new santier --using=laravel/laravel`
2. **Ziua 2** — Docker Compose funcțional + primele migrații
3. **Ziua 3** — Auth + roles (Spatie Permission) + layout de bază
4. **Săptămâna 1** — CRUD Proiecte + Clienți funcțional

---

---

## 17. DECIZII TEHNICE — JUSTIFICĂRI

| Decizie | Opțiune aleasă | Alternativă | Motiv |
|---|---|---|---|
| Gantt Faza 1 | Frappe Gantt | DHTMLX / Bryntum | Gratuit, integrare rapidă Vue |
| Gantt Faza 2+ | Bryntum Gantt | Syncfusion | Cel mai robust pentru producție |
| PDF devize | Browsershot (Puppeteer) | DomPDF | CSS complet → output profesional |
| Multi-tenancy F1–2 | `tenant_id` pe tabele | Stancl/tenancy | Suficient sub 50 firme, zero overhead |
| Multi-tenancy F3 | Stancl/tenancy | Schema per tenant | Izolare completă la scale |
| Calendar OAuth | Socialite + Google/MS Graph | iCal export simplu | Sync real-time, bidirecțional |
| Token storage | Laravel Encryption (`$casts`) | Plaintext / env | Tokens criptate în DB, niciodată loggate |
| Tasking | Modul intern simplu | Integrare Trello/Asana | Zero dependențe externe, UX simplificat |
| Snag List | Modul intern + Browsershot PDF | Tool dedicat extern | Integrat în flow, fără alt tool extern |

---

*Generat: 2026-06-25 | Versiune plan: 1.3 | **DEV ÎNCEPUT: 2026-06-25** — Faza 0: 6/11 taskuri completate*

---

## 18. JURNAL DEZVOLTARE

| Data | Versiune | Ce s-a făcut |
|---|---|---|
| 2026-06-25 | v0.1 | Setup complet: Laravel 13 + Inertia v3 + Vue 3 + Vite + MySQL + Spatie Permission/ActivityLog + Ziggy + Pinia. Build funcțional. |
