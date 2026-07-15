# Documente tipizate - plan de dezvoltare pe faze

Context: `documente.md` (scris de utilizator) descrie o problema reala - toate cele
14 tipuri de `Document` (contract, factura, aviz, PV receptie, PV constatare etc.)
folosesc azi exact acelasi formular, aceleasi campuri fixe si acelasi layout PDF,
care era in plus hardcodat cu titlul "Proces verbal de receptie" indiferent de
tipul real (bug pre-existent, corectat la Faza 1).

## 1. Decizii de scop confirmate
- **Oferta/Deviz raman excluse** din acest modul - exista deja ca sistem separat,
  matur (`App\Models\Quote`/`QuoteItem`, sabloane PDF, aprobare interna - vezi
  `billing-plans.md`). Nu se construiesc a doua oara aici.
- **Extensie tipizata, NU motor generic** - fiecare tip de document primeste
  campuri de formular + sectiuni PDF proprii, hardcodate in cod (acelasi tipar ca
  `App\Support\QuotePdfPresenter`), NU tabele `document_types`/`document_fields`
  configurabile din baza de date. Motivul: la doar cateva tipuri de tratat, un
  motor generic (mini form-builder) ar fi mult mai multa complexitate de
  intretinut decat beneficiul, fara alt precedent in aplicatie.
- **Stocare**: campurile specifice per tip se salveaza intr-o coloana JSON
  `documents.type_data` (nullable), exact acelasi tipar ca `quotes.meta` - evita o
  explozie de coloane SQL goale pentru restul tipurilor, dar continutul/validarea
  raman hardcodate per tip in cod, nu generice.
- **Semnaturile raman doar vizuale** in PDF (liniile de semnat deja existente) -
  nu se capteaza nume de semnatari ca date structurate.

## 2. Faze de dezvoltare
| # | Faza | Tipuri | Status |
|---|------|--------|--------|
| 1 | Fundatie + PV obligatorii legal | PV receptie lucrari, PV lucrari ascunse | **Facut** |
| 2 | Restul proceselor verbale | PV predare-primire, PV remediere defecte, PV constatare | **Facut** |
| 3 | Contract | Contract prestari servicii | Neinceput |
| 4 | Documente financiare/logistice | Factura, Aviz de insotire marfa | Neinceput |

## 3. Cum lucram pe fiecare faza
Acelasi flux stabilit deja in acest repo (vezi `organizare-santier.md`/`billing-plans.md`):
1. Alegem faza (confirmare directa).
2. `EnterPlanMode` cu cercetare in cod inainte de a scrie planul.
3. Implementare dupa aprobare, `npm run build`, teste PHP scrise (dar nerulabile
   din acest mediu - fara PHP CLI accesibil), commit + push.
4. Actualizam tabelul din sectiunea 2 (Status: Neinceput -> Facut) + o nota scurta
   in "## 4. Progres" dupa fiecare faza.

## 4. Progres

### Faza 1 - Fundatie + PV obligatorii legal (Facut, 2026-07-15)
- Migratie noua: `documents.type_data` (json, nullable, `after('notes')`) -
  acelasi tipar ca `quotes.meta`.
- `App\Models\Document`: tip nou `proc_verbal_lucrari_ascunse` in `$typeLabels`;
  `type_data` in `$fillable` + cast `array`.
- `App\Http\Requests\StoreDocumentRequest`: reguli suplimentare aplicate DOAR
  cand `type` e `proc_verbal_receptie` (comisie, descriere_lucrari, defecte,
  concluzie admis/respins) sau `proc_verbal_lucrari_ascunse` (descriere_lucrari_
  ascunse, verificari_efectuate, responsabil_tehnic) - restul celor 12 tipuri
  raman neschimbate.
- `DocumentController::store()`/`update()` nu au avut nevoie de nicio modificare -
  `type_data` trece automat prin `$request->validated()` si spread-ul existent
  `[...$validated, ...]`.
- `App\Support\DocumentPdfPresenter::present()`: adauga `typeData` in array-ul
  returnat; prefix cod intern nou pentru PV lucrari ascunse (`PVA`).
- `documents/pdf-classic.blade.php` + `pdf-modern.blade.php`: titlul devine
  dinamic (`$document->type_label`, era hardcodat "Proces verbal de receptie" -
  bug pre-existent corectat pentru toate cele 14 tipuri, nu doar cele 2 noi);
  sectiunile C ("Descrierea lucrarii") si D ("Constatari la receptie") au
  continut conditionat pe tip - cele 2 tipuri noi arata date structurate din
  `type_data`, restul tipurilor pastreaza continutul generic actual neschimbat.
- `Documents/Create.vue` + `Edit.vue`: blocuri de campuri suplimentare, vizibile
  doar cand tipul selectat e unul din cele 2 noi (`v-if`/`v-else-if` pe
  `form.type`); restul formularului neschimbat pentru celelalte 12 tipuri.
- Test `tests/Feature/TypedDocumentTest.php`: validare esueaza fara campurile
  noi obligatorii (ambele tipuri noi), succes + `type_data` salvat corect cand
  sunt complete, PDF raspunde 200 pentru ambele tipuri (clasic implicit), un tip
  vechi (`invoice`) ramane neschimbat (regression check).

### Faza 2 - Restul proceselor verbale (Facut, 2026-07-15)
- Acelasi tipar tehnic ca Faza 1, extins la 3 tipuri: **PV predare-primire**
  (tip nou `proc_verbal_predare_primire`), **PV remediere defecte** (tip nou
  `proc_verbal_remediere_defecte`) si **PV constatare** (tip existent
  `proc_verbal_constatare` - primeste pentru prima data campuri si sectiuni PDF
  proprii, nu mai cade pe formularul/PDF-ul generic).
- Fara migratie noua - `documents.type_data` (json) exista deja din Faza 1.
- `StoreDocumentRequest::typeDataRules()`: 3 branch-uri noi in `match` (aceleasi
  reguli descrise in plan - `stare_remediere` foloseste acelasi tipar binar
  `Rule::in()` ca `concluzie` de la PV receptie).
- `DocumentPdfPresenter`: prefixe cod intern noi (`PVP`, `PVD`; `PVC` exista deja
  pentru constatare).
- `documents/pdf-classic.blade.php` + `pdf-modern.blade.php`: sectiunile C si D
  extinse cu cate un branch per tip nou; pentru PV predare-primire sectiunea D
  ("Constatari...") se omite (nu se aplica conceptual, la fel ca PV lucrari
  ascunse); pentru PV remediere defecte sectiunea D devine "Stare remediere";
  pentru PV constatare sectiunea D devine "Martori si masuri".
- `Documents/Create.vue` + `Edit.vue`: 3 blocuri noi de campuri in lantul
  `v-if`/`v-else-if`, acelasi stil vizual ca Faza 1.
- `tests/Feature/TypedDocumentTest.php` extins (nu fisier nou): validare pentru
  cele 3 tipuri, plus PDF 200 pentru toate cele 5 tipuri tratate pana acum
  (clasic implicit).
