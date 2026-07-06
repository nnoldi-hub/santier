# Hostico + GitHub Deploy Checklist (Modulia 2.0)

Scop: publicare productie pe modulia.ro folosind GitHub ca sursa de adevar.

## 0. Strategie recomandata

- Foloseste branch `main` pentru productie.
- Nu urca secrete in Git (`.env`, chei, parole).
- Deploy pe Hostico se face din repository (Git Version Control din cPanel) sau prin pull manual daca ai SSH.

## 1. Pregatire repository GitHub

1. Verifica sync local -> GitHub:

```bash
git fetch origin
git rev-list --left-right --count origin/main...main
```

Rezultat dorit: `0 0`.

2. Verifica fisiere sensibile:
- `.env` trebuie sa fie ignorat in `.gitignore`.
- Nu comita fisiere cu parole/API keys.

3. Optional: adauga tag pentru release live:

```bash
git tag -a v2.0.0 -m "Modulia 2.0 production release"
git push origin v2.0.0
```

## 2. Pregatire Hostico (cPanel)

1. Creeaza baza de date in `MySQL Databases`:
- DB name
- DB user
- DB password
- Assign user to DB cu `ALL PRIVILEGES`

2. Seteaza PHP in `Select PHP Version`:
- minim 8.2 (ideal 8.2/8.3)
- extensii: bcmath, ctype, curl, fileinfo, json, mbstring, openssl, pdo_mysql, tokenizer, xml, zip, intl, gd

3. Activeaza SSL (AutoSSL / Let's Encrypt) pentru:
- `modulia.ro`
- `www.modulia.ro`

4. Verifica DNS:
- A record `modulia.ro` -> `188.241.222.239`
- CNAME `www` -> `modulia.ro`

## 3. Deploy cod din GitHub in Hostico

### Varianta A (recomandata): cPanel Git Version Control

1. In cPanel -> `Git Version Control` -> `Create`.
2. Clone URL: repo GitHub (HTTPS/SSH).
3. Branch: `main`.
4. Clone in folder non-public (exemplu):
- `/home/USER/modulia-app`

5. Dupa clone/pull:
- ruleaza `composer install --no-dev --optimize-autoloader`
- ruleaza `npm ci` si `npm run build` (daca hostingul permite Node)

Daca Node nu este disponibil pe hosting:
- rulezi `npm run build` local
- comiti `public/build` in branch-ul de productie (sau incarci build-ul prin File Manager)

### Varianta B: Urcare directa in GitHub din browser

Daca creezi repo nou direct pe GitHub si incarci fisiere:
1. Creeaza repo privat/public.
2. Upload fisiere proiect (fara `.env`, `vendor`, `node_modules`).
3. Commit in `main`.
4. In Hostico folosesti repo-ul rezultat (pasii de la Varianta A).

## 4. Structura web root Laravel

Obligatoriu: domeniul trebuie sa serveasca folderul `public` al Laravel.

Optiuni:
1. Daca Hostico permite document root custom:
- setezi root -> `/home/USER/modulia-app/public`

2. Daca nu permite root custom:
- aplicatia ramane in folder privat
- in `public_html` pui doar continutul din `public` si ajustezi `index.php` catre caile corecte ale aplicatiei

## 5. Configurare `.env` productie

In folderul aplicatiei pe server:

```env
APP_NAME=Modulia
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://modulia.ro

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=database
```

Note:
- `FILESYSTEM_DISK=public` este cheia corecta.
- nu exista variabila standard `STORAGE_LINK`.

## 6. Comenzi post-deploy (ordine)

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

Daca ai seed-uri necesare:

```bash
php artisan db:seed --force
```

## 7. Cron jobs esentiale

In cPanel -> `Cron Jobs`:

1. Scheduler Laravel (la fiecare minut):

```bash
* * * * * /usr/local/bin/php /home/USER/modulia-app/artisan schedule:run >> /dev/null 2>&1
```

2. Queue worker (daca folosesti cozi) - varianta shared hosting:
- rulezi periodic un command controlat (sau folosesti scheduler care porneste joburi)

## 8. HTTPS redirect

In `public/.htaccess`:

```apacheconf
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 9. Smoke test dupa lansare

1. Home + login + logout
2. Dashboard
3. Projects / Tasks / Materials
4. Defects photo upload
5. Quality Checks + PDF export
6. Notifications
7. Resource Calendar
8. Email flow (daca este activ)

## 10. Backup + observabilitate

1. Activeaza backup automat in Hostico.
2. Verifica loguri Laravel:
- `storage/logs/laravel.log`
3. Configureaza uptime monitor (ex. UptimeRobot).
4. Pastreaza backup DB inainte de fiecare migrare.

---

## Quick go-live in 20 minute (rezumat)

1. Push final pe GitHub (`main`).
2. Clone/pull pe Hostico din GitHub.
3. Configureaza `.env` productie.
4. `composer install --no-dev --optimize-autoloader`.
5. `php artisan migrate --force`.
6. `php artisan storage:link`.
7. `php artisan optimize`.
8. SSL + DNS + test final.

---

## Executie concreta pentru modulia.ro (pas cu pas)

Date confirmate:
- Domeniu: `modulia.ro`
- IP hosting: `188.241.222.239`
- Branch productie: `main`
- Repo GitHub: `https://github.com/nnoldi-hub/santier.git`

### Faza A - ce faci acum in GitHub (10 minute)

1. In GitHub, verifica repository-ul `nnoldi-hub/santier`.
2. Confirma ca branch-ul default este `main`.
3. Verifica ultimul commit de productie (local si remote sunt deja sincronizate).
4. Nu urca niciun fisier de tip `.env`, `vendor/`, `node_modules/`.

### Faza B - ce faci in Hostico cPanel (15-20 minute)

1. `MySQL Databases`:
- creezi baza de date, user si parola puternica
- dai `ALL PRIVILEGES` userului pe DB

2. `Select PHP Version`:
- setezi PHP 8.2 sau 8.3
- bifezi extensiile necesare (vezi Sectiunea 2)

3. `Git Version Control`:
- `Create`
- Clone URL: `https://github.com/nnoldi-hub/santier.git`
- Branch: `main`
- Clone path (exemplu): `/home/<cpanel_user>/modulia-app`

4. Web root Laravel:
- daca ai optiune de document root custom: `/home/<cpanel_user>/modulia-app/public`
- daca nu ai optiunea, folosesti varianta fallback din Sectiunea 4

5. SSL:
- activezi AutoSSL / Let's Encrypt pentru `modulia.ro` si `www.modulia.ro`

### Faza C - configurare productie (SSH/Terminal cPanel)

Ruleaza in folderul aplicatiei:

```bash
cd /home/<cpanel_user>/modulia-app
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

### Faza D - `.env` productie minim

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://modulia.ro

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<db_name>
DB_USERNAME=<db_user>
DB_PASSWORD=<db_pass>

FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
```

### Faza E - cron jobs

In `Cron Jobs` adaugi:

```bash
* * * * * /usr/local/bin/php /home/<cpanel_user>/modulia-app/artisan schedule:run >> /dev/null 2>&1
```

### Faza F - test final live (10 minute)

1. `https://modulia.ro` se incarca fara erori SSL.
2. Login / logout functioneaza.
3. Dashboard se incarca.
4. Defects upload foto functioneaza.
5. Quality Checks PDF se deschide corect.
6. Calendarul de resurse se incarca.

---

## Daca NU ai SSH in Hostico (fallback rapid)

1. Local rulezi:

```bash
composer install --no-dev --optimize-autoloader
npm run build
```

2. Incarci codul prin File Manager (fara `.env`, `vendor`, `node_modules`).
3. In cPanel folosesti `Terminal` (daca exista) doar pentru comenzile artisan.
4. Daca nu ai nici Terminal, rogi suportul Hostico sa ruleze comenzile:
- `php artisan migrate --force`
- `php artisan storage:link`
- `php artisan optimize`

---

## Lista de bifat pentru azi

- [ ] DB creata + user + privilegii
- [ ] PHP 8.2/8.3 + extensii active
- [ ] Repo clonat din GitHub in Hostico
- [ ] `.env` productie completat
- [ ] Comenzi Laravel rulate
- [ ] SSL activ + HTTPS ok
- [ ] Cron scheduler activ
- [ ] Smoke test complet
