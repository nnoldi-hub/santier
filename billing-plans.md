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
| 1 | Limite reale de utilizatori | `PricingPlan::canInviteUser()` (acelasi tipar ca `canCreateProject()`) + verificare in `TenantUserController::invite()` | Neinceput |
| 2 | White-label | Elimin conditionat textul/logo-ul "Modulia" hardcodat din PDF-uri (`documents/pdf.blade.php`, `exports/managerial-pdf.blade.php`) si emailuri (`quote-sent.blade.php`, `UserInvitedNotification`) cand `PricingPlan::hasFeature($user,'white_label')` | Neinceput |
| 3 | Sabloane multiple de documente | Concept nou (`document_templates`/picker UI/alte layout-uri Blade) - azi exista un singur layout per tip de document, nimic stubbed | Neinceput |
| 4 | Aprobari documente (facturare) | Necesita clarificare cu utilizatorul ce inseamna exact ("aprobari" - flux de sign-off inainte de trimiterea unui document catre client?) inainte de plan | Neinceput |
| 5 | Integrare plati (Stripe/Cashier) | Checkout real, webhook, ciclu de viata abonament (upgrade/downgrade/anulare), inlocuieste comutatorul intern de plan. **Blocaje externe**: necesita cont Stripe + chei de test de la utilizator; `laravel/cashier` trebuie verificat pentru compatibilitate cu Laravel `^13.8` (posibil prea nou fata de ce documenteaza Cashier azi) inainte de a incepe | Neinceput |
| 6 | Domeniu propriu per tenant | Cea mai grea din punct de vedere infrastructura (DNS, SSL, verificare domeniu) - hosting-ul actual e shared hosting Hostico/cPanel, trebuie verificat ce suporta inainte de a promite ceva | Neinceput |

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
(gol - nicio faza inceputa inca)
