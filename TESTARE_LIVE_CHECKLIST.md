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

## 6. UAT - Documente resurse (trasabilitate)

### 6.1 Acces si registru

- [x] Meniu lateral `Resurse > Documente resurse` vizibil si accesibil.
- [x] Pagina `Documente resurse` se incarca fara eroare 500.
- [x] Filtrele `Cautare / Tip resursa / Status / Proiect` functioneaza.
- [x] Butonul `+ Document resursa nou` deschide formularul de creare.

### 6.2 Creare inregistrare

- [x] Se poate crea comanda `Material` cu campurile obligatorii.
- [x] Se poate crea comanda `Utilaj` cu campurile obligatorii.
- [x] Inregistrarea apare in lista imediat dupa salvare.
- [x] Butonul `Detalii` deschide pagina de detaliu pentru comanda selectata.

### 6.3 Detalii si reconciliere

- [x] Sectiunea `Reconciliere cantitati` este afisata in pagina de detaliu.
- [x] Verificarile neaplicabile afiseaza `N/A - lipsesc documentele necesare pentru verificare`.
- [x] `Timeline trasabilitate` afiseaza minim evenimentul de creare comanda.
- [x] Cardurile rezumat (`Cantitate comandata`, `Diferenta maxima`, `Status`, `Valoare unitara`) sunt populate corect.

### 6.4 Flux discrepante si blocare la plata

- [ ] Caz sub prag: diferenta <= `0.20` unitati nu blocheaza plata.
- [ ] Caz peste prag: diferenta > `0.20` unitati seteaza status `Blocat la plata`.
- [ ] La blocaj se creeaza automat task de follow-up (prioritate `high`).
- [ ] La blocaj se trimite notificare `resource_discrepancy` catre responsabil.

### 6.5 Confirmari workflow

- [ ] Confirmare tehnica (sef santier/executie/calitate) muta statusul in `Verificata`.
- [ ] Confirmari tehnice complete muta statusul in `In validare financiara`.
- [ ] Confirmare financiara finala muta statusul in `Aprobata`.
- [ ] Orice respingere muta statusul in `Respinsa`.
- [ ] Daca exista discrepanta blocanta activa, statusul ramane `Blocat la plata` (precedenta maxima).

### 6.6 Criterii de acceptanta UAT

- [ ] Nu apar erori 500 in fluxul complet create -> details -> confirmations.
- [ ] Datele din reconciliere sunt coerente cu documentele atasate.
- [ ] Taskurile si notificarile automate apar doar pentru discrepante blocante.
- [ ] Fluxul este demonstrabil cap-coada in max. 5 minute in fata clientului.

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
