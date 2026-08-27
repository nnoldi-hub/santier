# Plan de actualizari Modulia

Data planului: 2026-08-27
Sursa cerintelor: [actualizare.md](actualizare.md)

## Obiectiv

Separarea clara dintre controlul SaaS al Superadminului si operarea unei firme de constructii, fara modificari riscante asupra datelor existente sau asupra fluxului contului demo.

Principiul de lucru: intai protejam si testam granitele de acces, apoi adaugam functii comerciale una cate una.

## Situatia verificata

- Exista `TenantContext::isSuperadmin()` si modelul `Tenant` pentru izolarea tenantilor.
- Exista deja Cashier/Stripe, sincronizare prin webhook si planuri comerciale.
- Exista pagini de administrare pentru firme, abonamente si dashboard comercial.
- Exista cont demo separat, cu date izolate.
- Meniul Superadmin este afisat separat, dar meniurile operationale raman vizibile pentru Superadmin.
- Rutele administrative sunt autentificate, dar verificarea administratorului este repetata in controllere; este necesara o protectie middleware centralizata.
- Statusul tenantului este acum limitat la `active` si `suspended`; trial-ul si expirarea sunt derivate din datele de billing.

## Reguli de siguranta

1. Nu stergem si nu modificam datele existente fara migrare reversibila.
2. Nu schimbam simultan schema de billing, fluxul Stripe si meniul.
3. Orice actiune Superadmin cu efect asupra unei firme se auditeaza.
4. Impersonarea nu schimba parole si nu permite escaladare de privilegii.
5. Contul demo ramane cont normal de tenant, separat de Superadmin.
6. Fiecare faza are teste automate si verificare manuala in browser.

## Tablou de progres

- [x] Faza 0 - inventariere si teste de regresie
- [x] Faza 1 - separare meniu Superadmin si dashboard implicit
- [x] Faza 2 - middleware `EnsurePlatformAdmin`
- [x] Faza 3 - dashboard global comercial
- [x] Faza 4 - firme, statusuri comerciale si abonamente
- [x] Faza 5 - impersonare controlata si auditata
- [x] Faza 6 - alerte de vanzari si churn
- [ ] Faza 7 - pachete, Stripe, facturare si cupoane
- [ ] Faza 8 - afiliati si campanii
- [ ] Faza 9 - storage, anunturi globale si securitate
- [ ] Faza 10 - verificare live si documentare operationala

## Faza 0 - Inventariere si regresie

### Actiuni

- Inventarierea rutelor administrative si operationale.
- Stabilirea unei singure reguli pentru Superadmin: `is_superadmin` plus configuratia de administratori existenta.
- Confirmarea comportamentului unui Superadmin fara tenant activ.
- Adaugarea testelor pentru acces prin UI, URL direct si request-uri POST/PATCH.

### Criterii de acceptare

- Utilizatorul normal acceseaza in continuare toate modulele tenantului.
- Contul demo ramane izolat si functional.
- Superadminul nu este blocat din zona de platforma.
- Niciun test existent de billing, IAM sau administrare nu regreseaza.

## Faza 1 - Meniu si moduri de aplicatie

### Navigatie Superadmin

- Dashboard Global
- Firme si abonamente
- Utilizatori platforma
- Dashboard comercial
- Pachete de preturi
- Facturare si incasari
- Cupoane si campanii
- Setari generale
- Anunturi globale
- Logs si securitate

### Navigatie tenant

Se pastreaza modulele actuale:

- Proiecte si planificare
- Resurse
- Financiar operational
- Calitate
- Documente
- Raportare
- Cont si organizatie

### Implementare

- Randarea layout-ului foloseste o singura configuratie pentru modul platforma sau modul tenant.
- Superadminul este directionat catre dashboard-ul global.
- Utilizatorul tenant este directionat catre dashboard-ul operational.
- Accesul la modulele ascunse ramane blocat si pe server, nu doar in navigatie.

## Faza 2 - Protectie middleware

Introducem middleware-ul `EnsurePlatformAdmin` pentru toate rutele `/admin/*` si actiunile aferente.

### Criterii de acceptare

- Un utilizator normal primeste `403` la orice ruta de platforma.
- Un Superadmin poate accesa toate rutele platformei.
- Verificarea nu depinde de tenantul activ.
- Protectia ramane compatibila cu testele si configuratia actuala.

## Faza 3 - Dashboard Global

Consolidam metricile existente intr-un dashboard cu:

- firme totale si active;
- utilizatori activi;
- MRR estimat;
- trial-uri care expira in 7 zile;
- conversie trial -> paid;
- conturi abandonate;
- lead-uri calde;
- abonamente anulate;
- plati esuate;
- activitate recenta de platforma.

Metricile se calculeaza server-side prin query-uri agregate. Nu incarcam toate datele brute in browser doar pentru statistici.

## Faza 4 - Firme si abonamente

### Actiuni

- Pagina globala cu cautare, filtre si detalii per firma.
- Status comercial calculat initial fara ruperea coloanei existente `status`.
- Diferentierea intre plan intern, status Stripe si status operational.
- Standardizarea treptata a starilor: `trial`, `active`, `past_due`, `cancelled`, `suspended`, `expired`.
- Pastrarea tenantului ca sursa principala pentru billing; coloanele vechi de billing de pe user se migreaza gradual.

### Criterii de acceptare

- Orice schimbare de plan sau status este auditata.
- Webhook-urile Stripe raman sursa pentru starea efectiva a abonamentului.
- Nu se modifica planul unei firme printr-o actiune neautorizata.

## Faza 5 - Impersonare

### Flux

- Superadminul selecteaza un utilizator al firmei.
- Aplicatia deschide o sesiune de impersonare limitata.
- Se afiseaza permanent identitatea utilizatorului impersonat si butonul de revenire.
- Se pastreaza Superadminul initial, utilizatorul tinta, tenantul, ora, IP-ul si user agent-ul.
- Revenirea la contul Superadmin este disponibila din orice pagina.

### Restrictii

- Fara resetare de parola prin impersonare.
- Fara promovare la Superadmin.
- Fara stergere definitiva sau actiuni Stripe sensibile.
- Toate actiunile sunt inregistrate in audit.

## Faza 6 - Alerte si churn monitor

Prima versiune foloseste alerte calculate la accesarea dashboard-ului:

- trial activ si foarte aproape de expirare;
- trial activ cu activitate intensa;
- firma fara proiecte dupa 48 de ore;
- firma fara login recent;
- abonament anulat;
- plata esuata;
- tenant suspendat.

Fiecare alerta are severitate, tenant, motiv, ultima activitate, actiune recomandata si status: noua, in lucru sau rezolvata.

Ulterior, calculul poate fi mutat intr-un job programat si livrat prin e-mail.

## Faza 7 - Stripe, preturi si cupoane

- Stripe ramane sursa pentru plati, facturi, failed payments si cancelari.
- Aplicatia pastreaza planul intern, limitele si informatiile pentru UI.
- In prima versiune, preturile Stripe raman configurate controlat; adminul poate gestiona limitele si etichetele.
- Cupoanele se creeaza sau se sincronizeaza prin Stripe, nu printr-un sistem local paralel.
- Testam cupoane valide, expirate, nerecunoscute si aplicarea lor in checkout.

## Faza 8 - Afiliati si campanii

Model minim propus:

- parteneri afiliati;
- link-uri de afiliere;
- atribuirea tenantului la sursa;
- comisioane eligibile;
- istoric de plata.

Recomandarea initiala este comision pentru primele 3 luni ale unui client platitor, cu eligibilitate dupa perioada de refund/anulare.

## Faza 9 - Storage, anunturi si securitate

### Storage

- Snapshot periodic al consumului per tenant.
- Top firme dupa spatiu utilizat.
- Praguri de alerta.
- Nicio stergere automata fara confirmare si audit.

### Anunturi globale

- Mesaj, tip, perioada de activitate si audienta.
- Banner inchis de utilizator, dar reactivabil de Superadmin.
- Audit pentru publicare, editare si dezactivare.

### Logs si securitate

- login-uri esuate;
- schimbari de rol;
- impersonare;
- modificari de plan si status;
- webhook-uri Stripe;
- schimbari de setari;
- erori critice.

Se extinde mecanismul existent `access_audit_logs`, fara un sistem paralel.

## Testare si publicare live

Pentru fiecare faza:

1. Teste Feature pentru autorizare, date si fluxuri.
2. Teste frontend/build pentru paginile Inertia noi.
3. Verificare manuala cu utilizator normal, Superadmin si cont demo.
4. Verificare a migrarilor pe baza de date de test.
5. Commit separat, cu mesaj descriptiv.
6. Push pe ramura de lucru sau `main`, in functie de politica repository-ului.
7. Verificare live dupa deploy: login, meniu, acces direct, dashboard si logout.

## Prima livrare recomandata

Prima livrare trebuie sa contina numai:

- Faza 0;
- Faza 1;
- Faza 2;
- testele de izolare si regresie.

Aceasta rezolva problema Superadminului cu risc redus. Functiile de billing, impersonare si automatizari se implementeaza ulterior, pe incrementuri mici si verificabile.

## Jurnal de modificari

| Data | Faza | Modificare | Verificare | Status |
|---|---|---|---|---|
| 2026-08-27 | Planificare | Plan initial adaugat in repository | Revizuire arhitecturala si stare Git | Finalizat |
| 2026-08-27 | Fazele 0-2 | Middleware platforma/tenant, navigatie separata si redirect dashboard | 4 teste de izolare, 12 teste admin, build Vite | Finalizat |
| 2026-08-27 | Faza 3 | Metrici globale server-side si rezumat Dashboard Global | 13 teste backend, build Vite | Finalizat |
| 2026-08-27 | Faza 4 | Filtrare server-side dupa status comercial in Firme & Abonamente | 2 teste dedicate, regresie 12 teste, sintaxa PHP | Finalizat |
| 2026-08-27 | Faza 5 | Impersonare utilizator tenant cu banner, revenire si audit | 3 teste dedicate, regresie 18 teste, build Vite | Finalizat |
| 2026-08-27 | Faza 6 | Inbox server-side pentru trial, conturi abandonate, firme suspendate si lead-uri calde | 6 teste dashboard, regresie 19 teste, build Vite | Finalizat |
