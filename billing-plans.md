# Planuri de facturare - plan de dezvoltare pe faze

Context: pagina publica de preturi (`Welcome.vue`, sursa `config/pricing.php`) promite
diferentieri intre planuri (Demo/Brand de baza/Brand complet/Enterprise) care azi sunt
doar partial reale. Verificat in cod (agent de explorare, 2026-07-14):

## 1. Ce e deja real (nu se reia)
- Limita de proiecte (`PricingPlan::canCreateProject()`, folosita in `ProjectController`).
- Acces Gantt, export CSV, export enterprise (XLSX/PDF/abonamente) - toate prin
  middleware `plan:{feature}` (`App\Http\Middleware\EnsurePlanFeature`).
- Comutator on/off pentru pagina de configurare branding documente.
- Fundatia e solida si usor de extins: `App\Support\PricingPlan` (metode statice
  simple, `config('pricing.plans')` ca sursa unica de adevar), `EnsurePlanFeature`
  middleware generic (`plan:{feature}` alias), tipar deja stabilit de urmat pentru
  fiecare faza noua.

## 2. Ce e doar text de marketing azi (fara nimic in cod)
- Limite de utilizatori (`users_limit` in config, niciodata citit).
- "Antet si footer" / "Template documente" - toate planurile primesc azi exact
  acelasi formular de branding (nume, logo, o culoare). Niciun concept de sablon.
- "Mai multe sabloane" (enterprise) - nu exista deloc.
- "Aprobari" (enterprise, `document_approvals`) - cheia exista doar in config, fara
  logica. Fara legatura cu `SitePlanApproval` din Organizare Șantier (coincidenta de
  nume - acela e despre planul de santier al unui proiect, nu despre facturare).
- White-label - "Modulia" e hardcodat necondiționat in PDF-uri si emailuri.
- Domeniu propriu - nu exista routing pe domeniu/subdomeniu per tenant.
- **Nu exista nicio integrare de plata** (fara Stripe/Cashier in `composer.json`,
  fara webhook, fara checkout). Butoanele de pe planurile platite duc la
  `/register` (cont gratuit) sau la formularul de contact - nimeni nu poate fi
  taxat azi. Schimbarea de plan se face doar din pagina interna de Billing, gandita
  explicit ca instrument de testare ("Schimba planul pentru a testa limitele
  comerciale"), nu ca flux comercial.

## 3. Faze de dezvoltare

| # | Faza | Domeniu | Status |
|---|------|---------|--------|
| 1 | Limite reale de utilizatori | `PricingPlan::canInviteUser()` (acelasi tipar ca `canCreateProject()`) + verificare in `TenantUserController::invite()` | **Facut** |
| 2 | White-label | Elimin conditionat textul/logo-ul "Modulia" hardcodat din PDF-uri, exporturi, oferte, export programat si notificari interne de echipa cand tenantul are `white_label` in plan | **Facut** |
| 3 | Sabloane multiple de documente | Concept nou (`document_templates`/picker UI/alte layout-uri Blade) - azi exista un singur layout per tip de document, nimic stubbed | **Facut** |
| 4 | Aprobari documente (facturare) | Sign-off intern (manager/owner) inainte de trimiterea unei Oferte catre client | **Facut** |
| 5 | Integrare plati (Stripe/Cashier) | Checkout real, webhook, ciclu de viata abonament (upgrade/downgrade/anulare), inlocuieste comutatorul intern de plan | **Facut** |
| 6 | Pagina publica de oferta + domeniu propriu per tenant | Pagina publica noua "Vezi oferta online" (nu exista deloc inainte) + domeniu propriu (Enterprise) doar pentru link-ul trimis clientului | **Facut** |

**Ordinea propusa**: fazele 1-2 sunt rapide si fara dependinte externe (bun punct de
plecare). Faza 3 e un proiect de sine statator (UI + layout-uri noi). Faza 4 are
nevoie de o discutie de clarificare inainte sa devina un plan. Faza 5 (plati) e cea
mai importanta comercial dar are dependinte externe (cont Stripe) - poate fi mutata
mai devreme daca ai deja cont Stripe pregatit. Faza 6 ramane ultima, posibil limitata
de hosting.

## 4. Cum lucram pe fiecare faza
Acelasi flux stabilit deja in acest repo (vezi `organizare-santier.md`/`nou.md`):
1. Alegem faza (confirmare directa).
2. `EnterPlanMode` cu cercetare in cod (agent de explorare daca e nevoie) inainte de
   a scrie planul.
3. Implementare dupa aprobare, `npm run build`, teste PHP scrise (dar nerulabile din
   acest mediu - fara PHP CLI accesibil), commit + push.
4. Actualizam tabelul din sectiunea 3 (Status: Neinceput -> Facut) + o nota scurta
   in "## 5. Progres" dupa fiecare faza.

## 5. Progres

### Faza 1 - Limite reale de utilizatori (Facut, 2026-07-14)
- `App\Support\PricingPlan`: metode noi `usersLimit()`, `canInviteUser()`,
  `usersLimitMessage()` - acelasi tipar ca `projectLimit()`/`canCreateProject()`/
  `projectLimitMessage()`. Numaratoarea foloseste `TenantUser::where('tenant_id',
  ...)->where('status', 'active')->count()` - un membru suspendat elibereaza locul.
- `TenantUserController::invite()`: verificare adaugata inainte de tranzactie -
  daca email-ul invitat NU e deja membru al tenantului (re-invitare/schimbare rol
  raman neblocate, chiar daca tenantul e la capacitate) SI limita e atinsa,
  `return back()->withInput()->with('error', ...)` - acelasi stil ca
  `ProjectController::store()`.
- Confirmat in cod: la inregistrare (`RegisteredUserController`), proprietarul
  contului ocupa deja 1 loc activ de la inceput - un tenant nou pe planul `free`
  (limita 1) nu poate invita pe nimeni fara upgrade.
- Test extins in `tests/Feature/PricingPlanLimitsTest.php` (nu fisier nou): plan
  `free` blocheaza orice invitatie noua, plan `starter` permite exact 2 invitatii
  (owner + 2 = limita 3) apoi blocheaza a treia, re-invitarea unui membru existent
  nu e blocata de limita.
- Ramas explicit in afara scopului: indicator vizual "X din Y locuri" in pagina de
  utilizatori (doar mesaj de eroare la limita in aceasta faza).

### Faza 2 - White-label (Facut, 2026-07-14)
- **Descoperire cheie**: `document_logo_url`/`company_name`/`app_name` au deja
  "Modulia" ca valoare implicita in `config/platform.php` - deci brand-ul Modulia nu
  venea doar din fallback-uri moarte din Blade (`?? 'Modulia'`), ci direct din
  `AppSetting::allForTenant()`. Rezolvarea corecta s-a mutat la sursa: `App\Support\
  DocumentBranding::resolve(int $tenantId): array` (metoda noua) asambleaza branding-ul
  o singura data si goleste `company_name`/`app_name`/`document_logo_url` DOAR cand
  tenantul e pe plan cu `white_label` SI inca are valoarea implicita (nu suprascrie
  branding-ul propriu al tenantului). `App\Support\PricingPlan` a primit
  `planForTenant()`/`tenantHasFeature()` - varianta pe `tenant_id` direct, fara
  `User`, necesara pentru job-uri in coada si alte contexte fara `Auth`.
- Aplicat in: `DocumentController::pdf()`, `QuoteController::pdf()`/`send()`,
  `ExportController::managerialPdf()`, `SiteOrganizationController::exportPdf()`,
  `RunExportSubscriptionJob` (export programat, tenant rezolvat direct din
  `ExportSubscription::tenant_id`, fara `Auth`), `QuoteSentMail`,
  `ScheduledExportMail`, si toate cele 4 notificari interne de echipa
  (`UserInvitedNotification`, `UserStatusChangedNotification`,
  `UserRoleChangedNotification`, `ProjectRoleChangedNotification`).
- Semnatura globala "Modulia - Șantierul devine clar." din view-ul vendor Laravel
  (`vendor/notifications/email.blade.php`, folosit de TOATE notificarile bazate pe
  `MailMessage`, inclusiv reset parola) a fost scoasa din view-ul global (nu avea
  acces la context de tenant) si re-adaugata condiționat, ca linie normala, in
  fiecare din cele 4 notificari cu context de tenant - comportament vizual identic
  pentru toti tenantii non-enterprise.
- **Bug pre-existent gasit si corectat, neinlegatura cu white-label**:
  `emails/quote-sent.blade.php` continea 2 linii de continut de INVITATIE
  ("Ai fost invitat in Modulia...", "Activeaza contul...") intr-un email de OFERTA -
  clar copy-paste gresit, sters indiferent de plan.
- Test `tests/Feature/WhiteLabelBrandingTest.php`: `DocumentBranding::resolve()`
  direct (enterprise fara branding propriu -> gol; free -> Modulia; enterprise CU
  branding propriu -> valorile proprii), `QuoteSentMail.whiteLabel` corect dupa plan
  (`Mail::fake()`), `UserInvitedNotification.whiteLabel` corect dupa plan
  (`Notification::fake()`).

### Faza 3 - Sabloane multiple de documente (Facut, 2026-07-15)
- Blocurile `@php` de calcul din `quotes/pdf.blade.php` si `documents/pdf.blade.php`
  au fost extrase in doua clase noi (`App\Support\QuotePdfPresenter::present()`,
  `App\Support\DocumentPdfPresenter::present()`) - sursa unica de adevar pentru
  datele derivate, folosita de ambele layout-uri vizuale ale fiecarui tip de
  document. Numite deliberat "Presenter" si nu "Template" ca sa nu se confunde cu
  `App\Models\QuoteTemplate` (feature existent, neinlegatura - reutilizare de
  continut la crearea unei oferte noi, nu aspect vizual).
- `quotes/pdf.blade.php` si `documents/pdf.blade.php` (fisierele vechi, unic
  layout) au fost sterse si inlocuite cu cate doua fisiere: `pdf-classic.blade.php`
  (continut vizual identic cu ce exista inainte) si `pdf-modern.blade.php` (nou -
  banda de antet plina cu culoarea de brand si text alb, titluri de sectiune ca
  etichete/pastile colorate, un "hero" cu totalul de plata / situatia financiara
  sub antet in loc de grid-ul de carduri, blocuri cu chenar rotunjit pentru
  sectiunile narative; tabelele raman `<table>`-based, dompdf nu suporta
  flexbox/grid).
- `App\Support\DocumentBranding::resolve()`: adauga `document_template` in
  array-ul rezultat, citit din `AppSetting`, FORTAT la `'classic'` daca tenantul nu
  are feature-ul `document_templates` pe plan (aparare in adancime la downgrade de
  plan) sau daca valoarea salvata nu e `classic`/`modern`. `config/platform.php`
  are cheia noua `document_template` (default `'classic'`) in `defaults`.
- `QuoteController::pdf()`/`::send()` si `DocumentController::pdf()` apeleaza acum
  presenterul relevant si randeaza view-ul dinamic `quotes.pdf-{template}` /
  `documents.pdf-{template}` in functie de `$branding['document_template']`.
- `DocumentBrandingController`: `index()` trimite catalogul fix de sabloane
  (`documentTemplates`) si `documentTemplatesAllowed`
  (`PricingPlan::hasFeature($request->user(), 'document_templates')`) catre
  Inertia; `update()` valideaza si salveaza `document_template`
  (`nullable, in:classic,modern`) - fara blocare la salvare, singurul punct de
  aplicare a regulii de plan ramane `DocumentBranding::resolve()`.
- `resources/js/Pages/Documents/Branding.vue`: sectiune noua "Sablon document"
  dupa selectorul de culoare, acelasi tipar de butoane pastila ca la culori;
  dezactivata vizual (opacitate redusa, click ignorat) cu textul "Disponibil de la
  planul Brand complet" cand `documentTemplatesAllowed` e `false`.
- **Bug pre-existent gasit si corectat, neinlegatura cu sabloanele**:
  footer-ul din `documents/pdf.blade.php` continea textul literal "H. Footer:"
  inaintea continutului real (leftover de la o eticheta de sectiune, niciodata
  curatat) - eliminat in ambele layout-uri noi.
- Test `tests/Feature/DocumentTemplateTest.php`: `DocumentBranding::resolve()` -
  tenant `starter` cu `document_template=modern` salvat manual tot primeste
  `'classic'` (aplicare fortata), tenant `pro` primeste `'modern'` daca l-a salvat,
  `pro` fara nimic salvat primeste `'classic'` implicit; HTTP - `GET
  /quotes/{quote}/pdf` si `GET /documents/{document}/pdf` raspund 200 atat pentru
  `classic` cat si pentru `modern`.

### Faza 4 - Aprobari documente (Facut, 2026-07-15)
- Scop confirmat cu utilizatorul: sign-off intern (un al doilea om din firma)
  inainte ca o **Oferta** sa poata fi trimisa clientului. Documentele (Proces
  verbal) raman neschimbate - nu au azi niciun flux de "trimitere catre client"
  (doar descarcare PDF), deci nu exista ce sa blocam acolo.
- Tipar reutilizat de la `SitePlanApproval` (Organizare Santier): coloane
  `internal_approved_at`/`internal_approved_by` direct pe `Quote` (migratie
  `2026_07_15_120000_...`) + tabel de audit `quote_approvals` (migratie
  `2026_07_15_130000_...`, model nou `App\Models\QuoteApproval`) - fiecare
  aprobare/dezaprobare adauga un rand cu `action`. Diferenta fata de
  `SitePlanApproval`: aici chiar exista o verificare de permisiune reala
  (`quotes.internal_approve`, permisiune noua, distincta de `quotes.approve` care
  ramane exclusiv pentru "clientul a acceptat oferta").
- `QuoteController::approveInternally()`/`unapproveInternally()` (noi, rutele
  `PATCH quotes/{quote}/approve-internally` / `unapprove-internally`, gatate de
  `permission:quotes.internal_approve` + `plan:document_approvals`).
  `QuoteController::send()` blocheaza trimiterea cu eroare daca tenantul are
  feature-ul `document_approvals` (`PricingPlan::tenantHasFeature()`) SI oferta nu
  e aprobata intern - tenantii fara acest feature (majoritatea) nu sunt afectati
  deloc, comportament identic cu inainte.
- `Quotes/Edit.vue`: banner (amber/emerald, acelasi stil ca in
  `SiteOrganization/Index.vue`) langa actiuni, vizibil doar cand
  `documentApprovalsEnabled`; buton "Aproba oferta"/"Anuleaza aprobarea" vizibil
  doar pentru useri cu `quotes.internal_approve`; butonul "Trimite clientului" e
  dezactivat client-side cat timp oferta nu e aprobata (blocajul real ramane
  server-side).
- Test `tests/Feature/QuoteInternalApprovalTest.php`: trimitere blocata pana la
  aprobare apoi deblocata (`Mail::fake()`), aprobare dubla -> 422, dezaprobare
  reblocheaza trimiterea, user fara permisiune -> 403, tenant `starter` (fara
  feature) trimite fara nicio aprobare si rutele de aprobare sunt blocate de
  `plan:document_approvals`.

### Faza 5 - Integrare plati Stripe (Facut, 2026-07-15)
- Verificat prin cautare web: `laravel/cashier` v16.6+ suporta Laravel 13 (riscul de
  compatibilitate anticipat in sectiunea 3 era real pana la o versiune recenta a
  pachetului, acum rezolvat). Proiectul ruleaza Laravel `v13.17.0` exact.
- **`Tenant` (nu `User`) e billable-ul Cashier** - `PricingPlan::current()` citeste
  planul de pe tenant intai, user doar ca fallback legacy; `Tenant` primeste
  `use Laravel\Cashier\Billable`, `Cashier::useCustomerModel(Tenant::class)` in
  `AppServiceProvider::boot()`. Migratie noua adauga `stripe_id`/`pm_type`/
  `pm_last_four`/`trial_ends_at` direct pe `tenants` (Cashier le pune implicit pe
  `users`, aici billable-ul e altul). `trial_ends_at` (Cashier) coexista intentionat
  cu `billing_trial_ends_at` (conceptul de trial existent, neschimbat, separat).
- `config/pricing.php`: fiecare plan platit primeste `stripe_price_id` (din env,
  `STRIPE_PRICE_STARTER/PRO/ENTERPRISE`); `free` ramane `null` (fara abonament
  Stripe). `PricingPlan::priceIdForPlan()`/`planForStripePrice()` (noi) fac
  conversia in ambele sensuri.
- **Decizii de business confirmate cu utilizatorul**: la coborare pe Demo,
  abonamentul se anuleaza dar tenantul pastreaza accesul pana la finalul perioadei
  deja platite (grace period Cashier standard, NU anulare instant); abonamentele
  noi nu au trial Stripe - taxare imediata la Checkout (trial-ul de 14 zile din
  aplicatie ramane un concept separat).
- `BillingController` rescris complet - vechiul `update()` (comutator instant,
  fara plata) e inlocuit de: `checkout($plan)` (Demo -> platit, redirect Stripe
  Checkout, fara abonament activ), `swap()` (schimbare intre planuri platite,
  sincron, fara redirect), `cancel()` (grace period - NU schimba planul imediat),
  `resume()` (revoca o anulare programata cat timp e in grace period), `portal()`
  (Stripe Customer Portal gazduit - card, facturi, anulare self-serve, un singur
  apel Cashier).
- `App\Http\Controllers\StripeWebhookController` (nou, extinde
  `Laravel\Cashier\Http\Controllers\WebhookController`) + logica de sincronizare
  extrasa in `App\Support\StripeSubscriptionSync` (clasa separata, testabila direct
  fara sa fie nevoie sa trimiti un webhook semnat catre HTTP): `applyUpdated()`
  traduce Price ID-ul curent -> cheie plan si scrie `Tenant.billing_plan` DOAR daca
  statusul e `active`/`trialing`; `applyDeleted()` seteaza `Tenant.billing_plan =
  'free'`. Ruta `POST /stripe/webhook` in `routes/web.php` (grup public, fara
  autentificare), exceptata de CSRF direct din `bootstrap/app.php`
  (`validateCsrfTokens(except: ['stripe/webhook'])`) - nu exista inca un grup `api`
  in aplicatie, nu s-a adaugat unul nou doar pentru asta.
- `resources/js/Pages/Billing/Index.vue` rescris: banner de grace period cu buton
  "Revoca anularea"; per card - "Plan activ" (disabled), "Renunta la abonament"
  (doar cardul Demo, doar daca exista abonament activ), "Abonare" (navigare
  completa catre Stripe Checkout, doar daca NU exista abonament activ) sau
  "Schimba planul" (schimbare in-app, doar daca EXISTA deja un abonament activ pe
  alt plan platit); link generic "Gestioneaza plata" catre Customer Portal.
- Test `tests/Feature/BillingStripeTest.php`: `PricingPlan::planForStripePrice()`
  direct; `StripeSubscriptionSync::applyUpdated()/applyDeleted()` testate direct cu
  payload-uri construite manual (fara nevoie de semnatura Stripe reala) - sincronizare
  corecta, statusuri neactive ignorate, client necunoscut = no-op; rutele
  `checkout`/`swap`/`cancel`/`resume`/`portal` testate pentru cazurile care NU ating
  Stripe (validari 422/404 inainte de orice apel extern) - fluxurile reale de plata
  (checkout/swap reusite) nu pot fi testate automat fara chei Stripe live.
- **Pasi manuali ramasi, doar utilizatorul poate sa-i faca** (vezi planul complet):
  creare 3 produse+preturi in Stripe Dashboard (mod test), configurare webhook
  endpoint catre `https://modulia.ro/stripe/webhook`, setare `STRIPE_KEY`/
  `STRIPE_SECRET`/`STRIPE_WEBHOOK_SECRET`/`STRIPE_PRICE_*` in `.env` pe server.
- **Deploy - pasi suplimentari fata de fazele anterioare**: `composer require
  laravel/cashier` (retea reala, ruleaza doar pe server, nu local),
  `php artisan vendor:publish --tag=cashier-migrations --tag=cashier-config`,
  `php artisan migrate --force`, apoi cele 6 variabile `.env` de mai sus,
  `optimize:clear`.

### Faza 6 - Pagina publica de oferta + domeniu propriu (Facut, 2026-07-15)
- **Descoperire care a schimbat scopul initial**: "domeniu propriu" presupunea o
  pagina publica existenta - nu exista deloc (`QuoteSentMail` trimitea doar PDF
  atasat, fara link; nicio ruta semnata; `Tenant.slug` complet decorativ). Faza a
  devenit doua lucruri: (A) pagina publica noua, (B) domeniu propriu deasupra ei -
  ambele confirmate cu utilizatorul.
- **Descoperire care a simplificat domeniul propriu**: pagina publica rezolva
  tenantul din `Quote.tenant_id` (route-model-binding), nu din domeniu - deci NU a
  fost nevoie de middleware nou de rezolutie tenant-din-domeniu. Domeniul propriu
  ramane strict cosmetic: ce hostname apare in link-ul din email.
- `App\Support\QuoteBreakdownResolver` (nou) - `extractBreakdownFromNotes()` si
  `buildBreakdownFromItems()` extrase din `QuoteController` (erau private, acum
  refolosite de 3 locuri: `pdf()`, `send()`, noul `PublicQuoteController`).
- `PublicQuoteController::show()` (nou) + ruta `GET oferte/{quote}/vizualizare`
  (in afara grupului `auth`, middleware `signed`) - randeaza `Public/QuoteShow.vue`
  (folder nou `Public/`, fara `AppLayout`) cu exact aceleasi date pe care clientul
  le primeste deja in PDF-ul atasat (via `QuotePdfPresenter::present()`) - nicio
  informatie noua expusa.
- `QuoteController::send()`: metoda noua `publicQuoteUrl()` genereaza link-ul
  semnat (`URL::signedRoute()`), permanent (nu expira - pagina arata doar un
  banner "oferta a expirat" cand e cazul, nu blocheaza accesul). Pentru tenant
  Enterprise cu `custom_domain` setat, link-ul se genereaza cu `URL::forceRootUrl()`
  temporar pe domeniul propriu (semnatura Laravel include host-ul, trebuie generata
  cu hostname-ul corect de la inceput ca sa valideze cand clientul acceseaza pe acel
  domeniu). `QuoteSentMail` + `emails/quote-sent.blade.php` primesc `publicUrl` si
  afiseaza un buton "Vezi oferta online" langa mentiunea PDF-ului atasat (PDF-ul
  ramane atasat, neschimbat).
- Migratie noua: `tenants.custom_domain` (nullable, unique). Setat DOAR de
  superadmin, nu self-service de catre tenant - activarea reala necesita oricum un
  pas manual in cPanel (addon domain), un formular self-service ar crea stari
  rupte. `AdminController::updateTenantCommercial()` (ecranul existent de
  management tenanti) primeste campul, golit automat daca planul selectat nu are
  feature-ul `custom_domain` (aparare in adancime, acelasi tipar ca la fazele
  anterioare). `Admin/TenantsIndex.vue` primeste coloana noua in tabelul de editare
  inline existent.
- **Hosting confirmat** (cautare web): Hostico ofera SSL automat gratuit
  (Let's Encrypt/AutoSSL) si domenii addon in cPanel pe planurile Business+.
  Adaugarea efectiva a domeniului addon (acelasi document root) ramane un pas
  MANUAL in cPanel, facut de utilizator per tenant enterprise - nicio integrare
  API cu cPanel in acest plan.
- Test `tests/Feature/PublicQuoteTest.php`: ruta publica fara semnatura -> 403; cu
  semnatura valida -> 200; semnatura unei oferte nu valideaza pentru alta ofertă
  (ID-ul e legat in semnatura); `send()` include `publicUrl` in `QuoteSentMail`
  (`Mail::fake()`).
- **In afara scopului**: verificare automata DNS, pagina publica pentru Documente
  (nu se trimit catre client azi), acces la intregul panou de administrare pe
  domeniul propriu (doar pagina publica de oferta).
