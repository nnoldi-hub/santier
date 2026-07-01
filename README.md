# SANTIER

Aplicatie web ERP pentru management proiecte de constructii/renovari.

Stack actual:
- Laravel 13 (backend)
- Inertia.js + Vue 3 + Pinia (frontend)
- MySQL
- Vite

## Status implementare (checkpoint curent)

Implementat:
- Autentificare si profile (Laravel Breeze + Inertia)
- Dashboard cu statistici de baza proiecte
- CRUD clienti
- CRUD proiecte
- Gestionare etape proiect (adaugare, actualizare, stergere, progres)
- Validari prin Form Requests pentru clienti, proiecte si etape
- Migrations pentru: users, jobs, cache, permissions, activity log, clients, projects, project_phases

Partial / placeholder:
- Task-uri proiect (placeholder in UI)
- Zone dashboard avansate (quotes/defects/teams doar placeholder)

Neimplementat inca (din planul mare):
- Modul ofertare/devize complet
- Modul echipe si alocari
- Defecte/snag list
- Gantt avansat (drag and drop, dependente)
- Raportare zilnica si consum materiale
- Notificari automate pe reguli

## Cerinte locale

- Windows + Laragon
- Node.js + npm
- MySQL pornit
- PHP din Laragon

## Configurare initiala

1. Instaleaza dependintele:

	npm install
	composer install

2. Configureaza mediul:

	copy .env.example .env
	php artisan key:generate

3. Configureaza baza de date in .env:

	DB_CONNECTION=mysql
	DB_HOST=127.0.0.1
	DB_PORT=3306
	DB_DATABASE=santier
	DB_USERNAME=root
	DB_PASSWORD=

4. Ruleaza migrarile:

	php artisan migrate

## Pornire locala (validat pe acest workspace)

In acest workspace, php nu este in PATH-ul terminalului VS Code, deci foloseste executabilul Laragon direct:

Backend:

	c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan serve --host=127.0.0.1 --port=8080

Frontend:

	npm run dev -- --host 127.0.0.1 --port 5173 --strictPort

URL-uri locale:
- App: http://127.0.0.1:8080
- Vite: http://127.0.0.1:5173

## Structura principala

- app/Http/Controllers: controllere pentru profile, clienti, proiecte, etape
- app/Http/Requests: validari request
- app/Models: User, Client, Project, ProjectPhase
- resources/js/Pages: pagini Inertia (Dashboard, Clients, Projects, Auth, Profile)
- routes/web.php: rute web + resource routes

## Testare rapida manuala

1. Login in aplicatie
2. Creeaza un client
3. Creeaza un proiect nou asociat clientului
4. In pagina proiectului, adauga 2-3 etape
5. Actualizeaza progresul etapelor
6. Verifica dashboard (proiecte recente + statistici)

## Cont demo public

Aplicatia include un cont demo public + set de date demo idempotent.

Credentiale implicite (override prin variabile `.env`):
- Email: `demo@santier.local`
- Parola: `Demo1234!`

Comanda de refresh demo:

	c:/n/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe artisan demo:refresh

Automatizare:
- `demo:refresh` ruleaza zilnic la `03:00` prin scheduler.

## Backlog imediat

Pentru planul Sprint 2 vezi fisierul SPRINT_2_AUDIT.md.

## Documente operationale de lansare

- Plan lansare executabil: LANSARE.MD
- Checklist final go-live: RELEASE_CHECKLIST.md
- Runbook operational: RUNBOOK_OPERATIONAL.md
- Timeline ziua lansarii: GO_LIVE_DAY.md
- Template raport post-lansare: POST_LAUNCH_REPORT_TEMPLATE.md

## Repo governance recomandat

Pentru ramura `main`, activeaza in GitHub urmatoarele reguli:

1. Require a pull request before merging.
2. Require approvals (minim 1).
3. Require status checks to pass before merging.
4. Include status check-ul din workflow-ul financiar:
	- `Financial Regression / Financial Test Bundle`

Fisiere de suport deja incluse in repo:
- `.github/pull_request_template.md`
- `.github/CODEOWNERS`
- `.github/workflows/financial-regression.yml`
