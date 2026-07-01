# RUNBOOK OPERATIONAL - SANTIER SAAS

## 1) Start local (Laragon)
Backend:
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan serve --host=127.0.0.1 --port=8080`

Frontend:
- `npm run dev -- --host 127.0.0.1 --port 5173 --strictPort`

## 2) Comenzi operationale esentiale
Migrations:
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan migrate --force`

Clear caches:
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan optimize:clear`

Queue worker:
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan queue:work --tries=3 --timeout=120`

Scheduler tick (local/manual):
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan schedule:run`

## 3) Joburi automate active
- `exports:run-scheduled` - every minute
- `emails:send-trial-lifecycle` - daily 09:00
- `demo:refresh` - daily 03:00

## 4) Operatiuni zilnice (checklist)
- [ ] Verifica health endpoint/app homepage.
- [ ] Verifica erori recente in logs.
- [ ] Verifica executia scheduler.
- [ ] Verifica queue backlog.
- [ ] Verifica funnel KPIs (signup, onboarding, first project, trial upgrade).
- [ ] Verifica pipeline firme pilot.

## 5) Procedura reset demo public
Comanda:
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan demo:refresh`

Rezultat asteptat:
- cont demo actualizat
- date demo reinitializate fara duplicate

## 6) Incident playbook
### Severitate P0
Exemple:
- login indisponibil
- onboarding indisponibil
- exporturi critice indisponibile

Actiuni in 0-15 minute:
1. Confirma incidentul si impactul.
2. Pune feature-uri non-critice pe hold.
3. Verifica logs + ultimul deploy + queue.
4. Daca nu exista fix in <30 minute, declanseaza rollback.

### Severitate P1
Exemple:
- bug major pe un modul non-core
- degradare performanta moderata

Actiuni:
1. Creeaza hotfix ticket.
2. Aplica mitigare temporara.
3. Programeaza patch in aceeasi zi.

## 7) KPI operationali minimi (saptamanal)
- Uptime aplicatie.
- Error rate endpoint-uri critice.
- Timp mediu raspuns pagini cheie.
- Conversie signup -> onboarding complet.
- Conversie onboarding -> primul proiect.
- Conversie signup -> trial upgraded.

## 8) Cadenta operare
Daily:
- check infrastructura + logs + queue + scheduler

Weekly:
- review KPI + top 5 probleme + prioritizare fixuri

Monthly:
- audit release quality + securitate + costuri + performanta

## 9) Test suite rapida post-change
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=EnterpriseExportsTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=OnboardingWizardTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=PricingPlanLimitsTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=FunnelAnalyticsTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=PilotInvitesTest`
