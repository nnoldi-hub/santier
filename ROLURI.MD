# Plan Profesional: Conturi, Roluri si Permisiuni

## 1. Obiectiv
Construim un sistem enterprise de Identity and Access Management (IAM) pentru Santier, cu separare multi-tenant, RBAC flexibil si audit complet.

Rezultatul dorit:
- separare stricta intre superadmin si firme
- roluri implicite + roluri custom per firma
- permisiuni granulare pe module/actiuni
- flux sigur de invitare, activare, suspendare si revocare acces

## 2. Principii de arhitectura
- Tenant-first: orice utilizator de firma este izolat in tenantul lui.
- Least privilege: fiecare rol primeste minimul de acces necesar.
- Explicit permissions: nu exista acces implicit pe module sensibile.
- Auditabilitate: fiecare actiune critica este logata.
- Feature gating: planul comercial controleaza accesul la module premium.

## 3. Nivele de cont
### 3.1 Superadmin (platform owner)
Poate:
- crea/suspenda/reactiva firme
- seta abonamente, limite, module disponibile
- vede plati, consum, erori, loguri agregate
- administra catalogul global de planuri si feature flags

Nu poate:
- vedea date operationale detaliate din tenant (proiecte, taskuri, oferte, documente)

### 3.2 Admin firma (tenant admin)
Poate:
- administra profilul firmei (branding, date, setari)
- invita utilizatori si atribui roluri
- activa/dezactiva roluri custom in tenant
- administra proiecte/oferte/documente in limitele planului

Nu poate:
- accesa zona de superadmin
- modifica planuri globale ale platformei

### 3.3 Utilizatori firma (roluri operationale)
Roluri implicite recomandate:
- Data Entry
- Quote Specialist
- Site Manager
- Finance
- Auditor (read-only)

Observatie:
- Admin firma poate crea roluri custom pe aceeasi grila de permisiuni.

## 4. Catalog module si actiuni
Module:
- quotes
- projects
- tasks
- calendar
- documents
- finance
- ai_tools
- company_settings
- users
- reports
- contractors
- equipment

Actiuni standard:
- view
- create
- edit
- delete
- export
- approve

Conventionare chei permisiune:
- module.action
- exemple: quotes.view, quotes.create, finance.view, users.edit

## 5. Matrice initiala de roluri
### 5.1 Data Entry
- materials/equipment/documents/tasks: view, create, edit
- projects: view
- fara delete pe entitati critice
- fara access la company_settings, users, finance

### 5.2 Quote Specialist
- quotes: view, create, edit, export
- templates: view, create, edit
- projects: view
- finance: view limitat (doar costuri necesare ofertarii)
- fara users/company_settings

### 5.3 Site Manager
- projects/tasks/calendar: view, create, edit
- project reports/photos: create, edit
- quotes: view
- fara finance complet, fara users/company_settings

### 5.4 Finance
- finance/reports: view, export, approve
- invoices/payments: view, create, edit
- quotes/projects: view
- fara create/edit pe taskuri/proiecte executionale

### 5.5 Auditor
- view pe modulele aprobate de tenant
- export optional
- zero create/edit/delete

## 6. Model de date recomandat
Entitati principale:
- tenants
- tenant_users (pivot user-tenant, status, department, invited_by)
- roles (scope: tenant_id nullable pentru roluri globale)
- permissions
- role_permissions (pivot)
- user_roles (pivot, cu tenant_id)
- invitation_tokens (email, role_id, tenant_id, expires_at)
- access_audit_logs (actor, tenant, action, resource, metadata, ip, user_agent)

Reguli cheie:
- fiecare resursa business are tenant_id
- user_roles este obligatoriu scoped pe tenant
- interzicem roluri cross-tenant

## 7. Fluxuri obligatorii
### 7.1 Provisioning firma
1. Superadmin creeaza tenant.
2. Seteaza plan, limite, module.
3. Creeaza adminul de firma.
4. Se trimite invitatie de activare.

### 7.2 Invitare utilizator
1. Admin firma invita prin email.
2. Alege rol (implicit sau custom).
3. Utilizatorul seteaza parola.
4. Contul devine active si apare in audit.

### 7.3 Offboarding
1. Admin dezactiveaza user.
2. Sesiuni active invalidate.
3. Tokenuri de invitatie resetate.
4. Audit cu motiv si actor.

## 8. Securitate si guvernanta
- middleware dublu: tenant scope + permission check
- politici Laravel (Policies) pentru ownership pe resurse
- rate limit pe login/invitatii/reset parola
- MFA optional pentru roluri sensibile (admin/finance)
- jurnal pentru actiuni critice: roluri, permisiuni, facturi, exporturi

## 9. Plan de implementare (8 saptamani)
### Faza 0 - Discovery si design (3-4 zile)
Livrabile:
- glossary IAM
- permission map final
- endpoint inventory afectat

Definition of Done:
- matrice roluri aprobata
- naming convention aprobata

### Faza 1 - Fundatie date si tenancy (Saptamana 1-2)
Livrabile:
- migrari pentru tenants/roles/permissions/pivoturi/invitatii/audit
- seed pentru permisiuni si roluri implicite
- tenant scoping pe modele critice

Definition of Done:
- teste integration pentru izolarea tenantului
- zero query fara tenant_id pe modulele business

### Faza 2 - Authorization backend (Saptamana 3)
Livrabile:
- middleware permission + tenant guard
- policies pe Quotes/Projects/Documents/Finance
- API pentru administrare roluri si permisiuni

Definition of Done:
- 403 corect pentru acces neautorizat
- teste feature pentru fiecare rol implicit

### Faza 3 - UI management utilizatori/roluri (Saptamana 4-5)
Livrabile:
- ecran Utilizatori (invita, suspenda, reactiva)
- ecran Roluri (implicit + custom)
- ecran Permission Matrix (toggle granular)

Definition of Done:
- admin firma gestioneaza complet accesul in tenant
- protectie UI + protectie backend consistente

### Faza 4 - Audit, securitate, hardening (Saptamana 6)
Livrabile:
- audit trail vizibil in UI superadmin/tenant admin
- rate limiting + session revocation
- evenimente security pentru actiuni critice

Definition of Done:
- audit complet pe fluxurile critice
- teste negative (escalation attempt) trecute

### Faza 5 - QA/UAT/Go-live (Saptamana 7-8)
Livrabile:
- regression suite pe modulele afectate
- UAT cu 2-3 tipuri de firme pilot
- runbook operational IAM

Definition of Done:
- zero blockers P1/P2
- rollout progresiv activ

## 10. Criterii de acceptanta pe produs
- un user nu poate accesa date din alt tenant
- un rol fara permisiune nu poate executa actiunea nici prin API
- orice schimbare de rol/permisiune este auditata
- admin firma poate opera 100% managementul utilizatorilor din tenant
- superadmin poate administra firma fara acces la datele operationale sensibile

## 11. Riscuri si mitigari
- risc: permission sprawl
- mitigare: naming strict + review lunar al rolurilor custom

- risc: bypass UI -> API direct
- mitigare: enforcement server-side exclusiv in middleware/policies

- risc: regresii pe module vechi
- mitigare: test matrix pe roluri x module in CI

## 12. Backlog imediat (urmatorul sprint)
1. Definire schema IAM finala si migrari.
2. Activare model tenant + tenant scoping pe resurse.
3. Seed permisiuni + roluri implicite.
4. Middleware + policies pentru quotes/projects/documents/finance.
5. Pagina admin pentru invitare utilizatori.
6. Teste feature pentru roluri critice.

## 13. Progres implementare (actualizat 2026-07-02)
Implementat deja:
- schema multi-tenant de baza: `tenants`, `tenant_users`, `users.tenant_id`, `users.current_tenant_id`, `users.is_superadmin`
- modele si context: `Tenant`, `TenantUser`, `TenantContext`
- integrare RBAC pe `User` (HasRoles)
- seeder IAM de bootstrap pentru permisiuni/roluri implicite (`IamSeeder`)
- migrare tenant context in Quotes, Projects, Documents, Material Invoices
- politici tenant-aware pentru: Project, Quote, Document, MaterialInvoice

Validat:
- migrate: OK
- seeding IAM: OK
- teste tintite (Project/Document/Quote/MaterialInvoice/CostTracking): PASS
- frontend build: PASS

Nota de rollout:
- enforcement-ul RBAC este activ prin Policies, cu fallback de compatibilitate pentru utilizatorii vechi fara roluri/permisii.
- dupa migrarea completa a utilizatorilor pe roluri, fallback-ul se elimina.

## 14. Urmatorul batch de implementare
Implementat in acest batch:
1. API + UI pentru management utilizatori tenant (invite/suspend/reactivate + schimbare rol).
2. API + UI pentru management roluri custom in tenant (create/update/delete + selectie permisiuni).
3. Permission matrix operational in UI prin selectie granulara `module.action` la crearea/editarea rolurilor.
4. Audit trail pentru schimbari IAM (invitare utilizator, schimbare status/rol, creare/editare/stergere rol).
5. Ecran dedicat de vizualizare audit trail in UI (`account/audit`) cu filtre (actor, actiune, resursa, perioada, tenant pentru superadmin).
6. UX simplificat in pagina de roluri: toggle `Ascunde cheile tehnice` + preseturi + selectie/deselectie permisiuni pe modul.
7. Roluri externe (client + subcontractor) cu acces limitat la proiectele proprii prin scope + policy.
8. Roluri dinamice pe proiect implementate: `project_roles` + `project_user_roles` cu niveluri `owner` / `contributor` / `viewer`.
9. Enforcements Batch 4:
	- scope dinamic in lista proiecte pentru utilizatorii care au asignari pe proiect;
	- `owner` poate view/update/delete;
	- `contributor` poate view/update, fara delete;
	- `viewer` are acces doar de vizualizare.
10. Seeder IAM extins cu rolurile implicite de proiect (`owner`, `contributor`, `viewer`).
11. Teste feature Batch 4 adaugate (`ProjectDynamicRolesTest`) + regresii Batch 3 mentinute verzi.
12. UI dedicat in pagina proiectului pentru asignare/actualizare/revocare roluri dinamice (`owner` / `contributor` / `viewer`).
13. Audit trail dedicat pentru `project_user_roles`:
	- `iam.project_role.assigned`
	- `iam.project_role.updated`
	- `iam.project_role.revoked`

Ramas de finalizat in batch-urile urmatoare:
1. API/UX pentru bulk-assign pe proiecte multiple (optional, pentru tenanturi mari).
2. Notificari email/in-app la schimbarea rolului dinamic pe proiect (optional).

Obiectiv finalizat: curatare hardcodari tenant (`tenant_id = 1`):
- inlocuit `tenant_id = 1` cu `TenantContext::id(...)` in Dashboard, controllerele operationale secundare si suport export dataset.
- validare: scan global `app/**` fara aparitii hardcodate + build frontend PASS.

Obiectiv finalizat: Batch 1 - activare middleware permission + pilot quotes/projects:
- activat alias middleware `permission` la nivel aplicatie (wrapper compatibil legacy pentru utilizatorii vechi fara roluri/permisii).
- pilot aplicat pe `projects` si `quotes` cu mapping pe actiuni (`view/create/edit/delete/approve`).
- regresie RBAC rulata (feature tests tintite): PASS.

Obiectiv finalizat: Batch 2 - audit trail UI + filtre + export:
- ecran audit IAM disponibil in `account/audit` pentru superadmin si admin firma.
- filtre active: actor, actiune, resursa, tenant (superadmin), interval data.
- export audit CSV implementat pe aceleasi filtre (`account/audit/export`).