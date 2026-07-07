# PLAN HOTFIX - PRIMELE 7 ZILE DUPA LANSARE

Scop: stabilizare productie in primele 7 zile, cu prioritizare pe impact client.

## Prioritati

- P0: indisponibilitate platforma / login / date corupte
- P1: flux critic blocat (proiecte, taskuri, defecte, quality PDF)
- P2: functionalitate degradata cu workaround
- P3: bug minor vizual/UX

## SLA intern recomandat

- P0: detectie imediata, fix/rollback in max 1h
- P1: triere in max 2h, fix in max 8h
- P2: fix in 24-48h
- P3: planificat in sprint urmator

## Cadenta zilnica (Ziua 1 -> Ziua 7)

1. 09:00 - health check
- status uptime
- erori 5xx ultimele 12h
- laravel.log erori critice

2. 14:00 - verificare fluxuri critice
- login/logout
- creare proiect + task
- defect cu foto
- quality PDF
- calendar resurse

3. 18:00 - raport scurt de stabilitate
- incidente noi
- incidente inchise
- risc pentru ziua urmatoare

## Checklist tehnic zilnic

- [ ] Verifica `storage/logs/laravel.log` (fara erori critice noi)
- [ ] Verifica Apache error log
- [ ] Verifica scheduler cron executat
- [ ] Verifica spatiu disk si DB growth
- [ ] Verifica export PDF (cel putin 1 test)

## Lista tipica de hotfix (template)

| ID | Severitate | Modul | Descriere | Impact | Owner | ETA | Status |
|---|---|---|---|---|---|---|---|
| HF-001 | P1 | Defecte | ____ | ____ | ____ | ____ | OPEN |
| HF-002 | P2 | Quality | ____ | ____ | ____ | ____ | OPEN |
| HF-003 | P3 | UI | ____ | ____ | ____ | ____ | OPEN |

## Fereastra de deploy hotfix

- Hotfix normal: 20:00 - 22:00
- Hotfix urgent P0/P1: imediat, cu anunt intern

## Protocol deploy hotfix

1. Branch `hotfix/<id>` din `main`
2. Implementare minima (scope restrans)
3. Test local + smoke productie
4. Merge in `main`
5. Deploy productie
6. `php artisan optimize:clear && php artisan optimize`
7. Verificare post-deploy 15 minute

## Criterii de inchidere perioada de stabilizare (dupa ziua 7)

- Fara incidente P0/P1 in ultimele 72h
- Error rate stabil in limite normale
- Fluxurile critice PASS in 3 zile consecutive
- Backlog hotfix ramas doar P3
