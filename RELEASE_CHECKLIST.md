# RELEASE CHECKLIST - SANTIER SAAS

Status: READY FOR GO-LIVE
Owner: Product + Tech Lead

## 1) Pre-release freeze
- [ ] Scope freeze confirmat pentru release.
- [ ] Doar bugfix-uri critice permise dupa freeze.
- [ ] Tag release pregatit in git (ex: v1.0.0).

## 2) Functional gates (must pass)
- [ ] Autentificare + onboarding 3 pasi functioneaza.
- [ ] CRUD: clienti, proiecte, taskuri, defecte, echipe, materiale, oferte.
- [ ] Exporturi CSV/XLSX/PDF functionale.
- [ ] Subscription export email functioneaza (queue + scheduler).
- [ ] Pricing gates active (Free/Starter/Pro/Enterprise).
- [ ] Analytics funnel afiseaza date.
- [ ] Modul Pilot Invites functioneaza.

## 3) Data and security gates
- [ ] Backup DB validat si test de restore facut.
- [ ] Credentiale productie rotite (APP_KEY, DB, SMTP).
- [ ] Demo account configurat separat pentru mediu demo.
- [ ] Logging activ pentru evenimente critice.
- [ ] Permisiuni endpoint-uri verificate (auth + onboarding + pricing).

## 4) Infra and operations gates
- [ ] `APP_ENV=production`, `APP_DEBUG=false` in productie.
- [ ] Queue worker pornit permanent.
- [ ] Scheduler activ (`schedule:run`).
- [ ] Cache config/routes/views regenerat dupa deploy.
- [ ] Healthcheck aplicatie validat.

## 5) Commercial readiness gates
- [ ] Landing page + CTA trial validate.
- [ ] Pricing final confirmat.
- [ ] Trial lifecycle emails validate (welcome/day3/day10/upgrade).
- [ ] Lista initiala firme pilot introdusa in modulul Pilot Invites.
- [ ] Script de demo comercial pregatit (15 min).

## 6) Final regression test suite
Ruleaza minim urmatoarele teste inainte de lansare:

- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=EnterpriseExportsTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=OnboardingWizardTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=PricingPlanLimitsTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=TrialLifecycleEmailsTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=FunnelAnalyticsTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=PublicDemoRefreshTest`
- `c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan test --filter=PilotInvitesTest`

## 7) Go-live execution
- [ ] Deploy artefact in productie.
- [ ] Ruleaza migrari.
- [ ] Ruleaza `optimize:clear`.
- [ ] Reporneste worker-ele.
- [ ] Ruleaza smoke test manual pe fluxurile critice.
- [ ] Anunta echipa: GO-LIVE complete.

## 8) Rollback criteria (strict)
Declanseaza rollback daca apare oricare din situatiile:
- Error rate > 5% pe fluxuri critice in primele 30 minute.
- Login, onboarding sau exporturi indisponibile > 10 minute.
- Coruptie date sau issue de securitate.

## 9) Rollback playbook (rapid)
- [ ] Pune aplicatia in maintenance mode.
- [ ] Revino la release anterior.
- [ ] Ruleaza rollback migrari doar daca este sigur si validat.
- [ ] Goleste cache si reporneste servicii.
- [ ] Verifica smoke tests.
- [ ] Comunica incident + ETA relansare.
