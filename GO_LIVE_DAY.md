# GO LIVE DAY PLAN - SANTIER SAAS

Data: 2026-06-30
Release: v1.0.0

## Obiectiv
Lansare controlata in productie cu risc minim, monitorizare activa in primele 24h si rollback rapid daca gate-urile critice sunt incalcate.

## Echipa si roluri
- Incident Commander (IC): Tech Lead
- Release Owner: Product Owner
- App Operator: Backend Engineer
- Frontend Operator: Frontend Engineer
- Monitoring Owner: DevOps/Infra
- Comunicari: Product + Support

## T-24h (cu o zi inainte)
1. Freeze de scope confirmat (doar hotfix critic).
2. Ruleaza suita minima de regresie:
   - EnterpriseExportsTest
   - OnboardingWizardTest
   - PricingPlanLimitsTest
   - TrialLifecycleEmailsTest
   - FunnelAnalyticsTest
   - PublicDemoRefreshTest
   - PilotInvitesTest
3. Verifica backup DB si test de restore.
4. Verifica credentiale productie (APP_KEY, DB, SMTP).
5. Confirma versiunea finala/tag-ul release.

## T-2h (pregatire imediata)
1. Brief de lansare cu toata echipa (15 min):
   - cine executa
   - cine valideaza
   - cine comunica
2. Verifica dashboard monitorizare:
   - error rate
   - latency
   - queue backlog
3. Verifica acces la servere/servicii pentru toti ownerii.
4. Pregateste mesajele de status (success/incident).

## T-30m (pre-deploy checks)
1. Verifica din nou ca nu exista modificari in afara release-ului.
2. Ruleaza smoke local rapid pe fluxurile critice.
3. Confirmare finala GO de la IC + Release Owner.

## T0 (deploy productie)
1. Deploy artefact release in productie.
2. Ruleaza migrari:
   - c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan migrate --force
3. Curata cache:
   - c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan optimize:clear
4. Reporneste queue workers.
5. Verifica scheduler activ.

## T+15m (smoke productie)
1. Login + onboarding 3 pasi.
2. Creare proiect/client/task.
3. Export CSV/XLSX/PDF.
4. Verifica pagina analytics.
5. Verifica modul Pilot Invites.

## T+30m (gate decizie)
Conditii de continuare fara rollback:
- Error rate <= 5% pe fluxuri critice.
- Niciun blocaj major pe login/onboarding/exporturi.
- Queue procesata normal, fara crestere anormala backlog.

Daca oricare conditie este incalcata:
- Declanseaza rollback conform RELEASE_CHECKLIST.md.

## T+2h (stabilizare)
1. Monitorizare activa in intervale de 15 minute.
2. Triere rapida pentru buguri minore (P1/P2).
3. Comunicare status intern la fiecare 30 minute.

## T+8h (health review)
1. Review metrici:
   - uptime
   - error rate
   - timp mediu raspuns
2. Review funnel initial:
   - signup
   - onboarding complet
   - primul proiect creat
3. Decide daca se mentine war-room sau se revine la monitorizare standard.

## T+24h (post-launch review)
1. Incident review (daca a fost cazul).
2. Lista de imbunatatiri post-lansare.
3. Actualizare roadmap pentru sprintul urmator.
4. Raport scurt de lansare catre stakeholderi.

## Plan de comunicare
- T0: "Deploy in progres"
- T+30m: "Go/No-Go status"
- T+2h: "Stare stabilizare"
- T+24h: "Raport final lansare"

## Criterii de succes in ziua lansarii
- Niciun incident P0.
- Fluxurile critice functionale end-to-end.
- Metrici in limitele acceptate.
- Echipa confirma intrarea in operare normala.
