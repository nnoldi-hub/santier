# HARDENING FINAL CHECKLIST (15 MIN) - MODULIA LIVE

Scop: inchidere rapida si sigura dupa go-live.
Tinta: fara secrete expuse, email stabil, backup valid, monitorizare activa.

## 1) Secrete si acces (5 min)

- [ ] Schimba parola DB in cPanel (MySQL user productie).
- [ ] Actualizeaza parola noua in `/home/rlwrgzez/repositories/modulia-app/.env`.
- [ ] Schimba parola `no-reply@modulia.ro` in cPanel Email Accounts.
- [ ] Actualizeaza `MAIL_PASSWORD` in `.env` cu noua parola.
- [ ] Schimba parola contului `admin@modulia.ro` din aplicatie.
- [ ] Sterge scripturi/fisiere temporare:
  - [ ] `/home/rlwrgzez/repositories/modulia-app/set_superadmin.php`
  - [ ] `/home/rlwrgzez/public_html/test.php`
  - [ ] `/home/rlwrgzez/public_html/test-autoload.php`

## 2) Rebuild config dupa schimbari (1 min)

Ruleaza pe server:

```bash
cd /home/rlwrgzez/repositories/modulia-app
php artisan optimize:clear
php artisan optimize
```

- [ ] Comenzile de mai sus au rulat fara erori.

## 3) Hardening email (4 min)

- [ ] SPF activ pentru domeniu (Hostico Email Deliverability).
- [ ] DKIM activ pentru domeniu.
- [ ] DMARC setat (minim):

```txt
v=DMARC1; p=quarantine; rua=mailto:security@modulia.ro; fo=1
```

- [ ] Test `Forgot Password` -> email ajunge in Inbox.
- [ ] Verificare Spam/Junk pentru primele trimiteri.

## 4) Operare si backup (3 min)

- [ ] Cron scheduler activ:

```txt
* * * * * /usr/local/bin/php /home/rlwrgzez/repositories/modulia-app/artisan schedule:run >> /dev/null 2>&1
```

- [ ] Backup DB facut dupa go-live.
- [ ] Backup fisiere facut (cel putin `public_html` + `storage`).

## 5) Monitorizare 24h (2 min)

- [ ] Uptime monitor activ pentru:
  - [ ] `https://modulia.ro`
  - [ ] `https://modulia.ro/login`
- [ ] Verificare log Laravel:

```bash
tail -n 120 /home/rlwrgzez/repositories/modulia-app/storage/logs/laravel.log
```

- [ ] Fara erori critice recurente in ultimele 24h.

---

## Criteriu de inchidere

Bifezi GO FINAL cand toate punctele de mai sus sunt complete si:

- [ ] Login/Logout OK
- [ ] Defecte + foto OK
- [ ] Quality PDF OK
- [ ] Calendar resurse OK
- [ ] Reset parola prin email OK
