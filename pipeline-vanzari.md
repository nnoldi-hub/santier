# Pipeline comercial - thread de email cu prospectii

## Context

Aplicatia are deja un pipeline de vanzari (`PilotInvite` + `CommercialTask` +
`CommercialAction` + `Admin/CommercialDashboard.vue`) care acopera prospectarea
si urmarirea unui lead de la "invitat" pana la "closed_won" (client activ).
Utilizatorul a cerut un mod de a tine legatura cu prospectii/clientii direct
din aplicatie - discutat si confirmat: prioritate pe **thread de email**
(nu chat intern intre colegi, amanat pentru mai tarziu).

Inainte de aceasta faza: emailurile trimise (`PilotInvitationMail`) aveau
`replyTo` = adresa personala a colegului responsabil - raspunsurile ajungeau
in inbox-ul lui personal, nimic din ele nu era vizibil in aplicatie.
`CommercialAction` (action_type `email`) era doar o notita text scrisa
manual, fara continutul real al mesajului.

## Faza 1 - Thread bidirectional prin cutia comuna vanzari@modulia.ro (Facut, 2026-07-28)

- **Model nou `PilotInviteMessage`** (`pilot_invite_messages`): pastreaza
  fiecare mesaj (intrat sau iesit) cu `direction`, `from_email`/`from_name`,
  `subject`, `body` (text simplu, fara HTML brut - la citire se extrage
  `text/plain` daca exista, altfel `strip_tags()` pe HTML), `message_id`
  (unic, cheia de idempotenta la citirea repetata prin IMAP),
  `in_reply_to_message_id`, `occurred_at`. Se scrie si un `CommercialAction`
  (action_type `email`) la fiecare mesaj, ca sa ramana consistent cu
  rezumatul deja afisat in `PilotInvites/Index.vue`.
- **Citire prin IMAP**: pachet nou `webklex/laravel-imap`, comanda noua
  `emails:poll-prospect-inbox` (`App\Console\Commands\PollProspectInboxCommand`),
  programata la fiecare 5 minute in `routes/console.php` (acelasi interval
  ca `briefing:send-daily`, deja existent). Conecteaza la cutia configurata
  in `.env` (`IMAP_HOST/PORT/ENCRYPTION/USERNAME/PASSWORD`), citeste mesajele
  din ultimele 3 zile, sare peste cele deja importate (dupa `message_id`,
  nu dupa flag-ul necitit - robust chiar daca cineva verifica manual cutia
  prin webmail), potriveste dupa `from_email == contact_email` pe cel mai
  recent `PilotInvite` cu acel email. Fara potrivire -> doar logat
  (`Log::warning`), nu se creeaza nimic - o cutie de mesaje neasociate ramane
  in afara scopului acestei faze.
- **Extragerea continutului**: `App\Support\InboundEmailMapper` (functie
  pura, fara dependinta de IMAP, testabila direct cu array-uri - vezi
  `tests/Unit/InboundEmailMapperTest.php`).
- **Raspuns trimis din aplicatie**: pagina noua `PilotInvites/Show.vue`
  (link din numele companiei in `PilotInvites/Index.vue`) - conversatie
  cronologica unificata (mesaje email + actiuni comerciale manuale), camp de
  compunere la subsol. `PilotInviteController::sendMessage()` trimite
  `App\Mail\PilotInviteThreadReplyMail` de la adresa din setarea existenta
  `sales_email` (Admin -> aceeasi folosita si la broilere PDF), seteaza
  explicit header-ele `Message-ID`/`In-Reply-To`/`References` ca raspunsul
  sa apara corect inlantuit si in clientul de email al prospectului.
- **Fara import de istoric** - thread-ul incepe gol pentru prospectii deja
  existenti (nu exista de unde, replierile vechi au mers in inboxuri
  personale).

### Limitari asumate explicit
- Comanda de citire IMAP propriu-zisa nu poate fi testata automat (nu exista
  server IMAP real/simulat in suita) - verificata manual prin SSH dupa
  deploy (`php artisan emails:poll-prospect-inbox`).
- `sales_email` (setarea din Admin) si contul IMAP din `.env` descriu
  aceeasi cutie fizica, dar sunt configurate separat - daca schimbi
  `sales_email` din Admin, trebuie actualizate manual si `IMAP_USERNAME`/
  `IMAP_PASSWORD` in `.env` pe server ca sa ramana aceeasi cutie.
- Fara atasamente in mesajele de thread (doar text, ca la `CommercialAction`).
- Fara notificari in timp real - polling la 5 minute, suficient pentru acest
  volum.

### Pasi manuali ramasi (doar utilizatorul poate sa-i faca)
- Confirmare ca `vanzari@modulia.ro` are IMAP activat in cPanel (de regula
  activ implicit pentru orice cont de email cPanel) si obtinerea host/port
  IMAP exacte (cPanel -> Email Accounts -> Connect Devices, similar cu ce
  s-a facut deja pentru SMTP).
- Setare in `.env` pe server: `IMAP_HOST`, `IMAP_PORT` (tipic 993),
  `IMAP_ENCRYPTION=ssl`, `IMAP_USERNAME=vanzari@modulia.ro`,
  `IMAP_PASSWORD` (parola reala a cutiei).

### Deploy - pasi suplimentari fata de fazele anterioare
`composer require webklex/laravel-imap` (retea reala, ruleaza pe server, nu
doar `composer dump-autoload`), apoi cele 5 variabile `.env` de mai sus,
`php artisan migrate --force` (tabela noua `pilot_invite_messages`),
`php artisan optimize:clear`, Deploy HEAD Commit din cPanel (pagina Vue
noua). Verificare: trimit un email de test catre `vanzari@modulia.ro` de pe
o adresa care corespunde unui `contact_email` existent, rulez manual
`php artisan emails:poll-prospect-inbox` prin SSH si confirm ca apare in
thread; trimit un raspuns din pagina `Show.vue` si confirm ca ajunge pe
email cu subiectul si header-ele corecte.
