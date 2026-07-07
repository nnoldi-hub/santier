# TESTARE LIVE CHECKLIST - MODULIA

Status tinta: LIVE STABIL
Scope: verificari imediat dupa lansare pe modulia.ro

## 1. Curatare dupa setup (obligatoriu)

- [ ] Sterge scripturi temporare de administrare:
  - `/home/rlwrgzez/repositories/modulia-app/set_superadmin.php`
- [ ] Sterge fisiere de test public:
  - `/home/rlwrgzez/public_html/test.php`
  - `/home/rlwrgzez/public_html/test-autoload.php`
- [ ] Schimba parola DB in cPanel si actualizeaza `.env` pe server.
- [ ] Schimba parola contului superadmin dupa primul login.

## 2. Verificari platforma (smoke test)

- [x] Home page: `https://modulia.ro` se incarca fara eroare.
- [x] Login/Logout functioneaza cu cont admin.
- [x] Onboarding 3 pasi se poate salva complet.
- [x] Dashboard se incarca fara erori JS/PHP.
- [x] Creeaza proiect nou.
- [x] Creeaza task nou.
- [x] Upload foto defect functioneaza.
- [x] Quality checks: listare + creare + editare.
- [x] Export PDF quality checks functioneaza.
- [x] Calendar resurse se incarca (inclusiv filtrare pe interval).

## 3. Verificari tehnice productie

- [ ] `.env` productie corect:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://modulia.ro`
  - `FILESYSTEM_DISK=public`
- [ ] Ruleaza cache refresh:
  - `php artisan optimize:clear`
  - `php artisan optimize`
- [ ] Verifica scheduler activ in cron (la fiecare minut):
  - `* * * * * /usr/local/bin/php /home/rlwrgzez/repositories/modulia-app/artisan schedule:run >> /dev/null 2>&1`

## 4. Loguri si monitorizare

- [ ] Verifica log Laravel dupa primele accesari:
  - `tail -n 120 /home/rlwrgzez/repositories/modulia-app/storage/logs/laravel.log`
- [ ] Verifica log Apache daca apare 500:
  - `tail -n 120 /home/rlwrgzez/access-logs/error_log`
- [ ] Activeaza monitor uptime (ex: UptimeRobot) pentru:
  - `https://modulia.ro`
  - `https://modulia.ro/login`

## 5. Confirmare finala GO

- [ ] Fara erori 500 in primele 30 minute.
- [ ] Fara erori critice in loguri.
- [ ] Fluxurile critice sunt functionale end-to-end.
- [ ] Backup initial realizat (DB + fisiere).

---

## Comenzi utile (server)

```bash
cd /home/rlwrgzez/repositories/modulia-app
php artisan optimize:clear
php artisan optimize
tail -n 120 storage/logs/laravel.log
```

---

## Status validare live (2026-07-07)

Validat pe productie, conform capturilor:

- [x] Home page live se incarca (`modulia.ro`)
- [x] Login superadmin functioneaza
- [x] Dashboard se incarca fara 500
- [x] Onboarding finalizat
- [x] Creare proiect functioneaza
- [x] Creare task functioneaza
- [x] Afisare etapa proiect + alocari in proiect
- [x] Notificari in aplicatie functionale

Inca de validat pentru semnare GO completa:

- [x] Defecte: creare + upload foto (inclusiv afisare poza in lista)
- [x] Quality checks: creare + export PDF
- [x] Calendar resurse: incarcare + filtrare
- [x] Logout + re-login
- [ ] Curatare fisiere temporare (`test.php`, `test-autoload.php`, `set_superadmin.php`)
- [ ] Rotatie parole (superadmin + DB)
