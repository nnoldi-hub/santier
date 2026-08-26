Problema actuală (și de ce trebuie rezolvată)
Din modulul tău:

„Procese verbale” folosesc același formular ca „Documente generale”

câmpuri inutile apar în documente (ex: „plătit/neplătit” la proces verbal)

nu există diferențiere între:

proces verbal de recepție

proces verbal de predare-primire

proces verbal de calitate

proces verbal de lucrări ascunse

proces verbal de remediere defecte

proces verbal de constatare

nu există sabloane diferite pentru:

ofertă

deviz

factură

contract

aviz

proces verbal

nu există câmpuri obligatorii conform legislației

nu există layout-uri diferite (toate PDF-urile arată la fel)

nu există white-label real

nu există flux de aprobare documente

Concluzie:  
Modulia are nevoie de Documente Tipizate, nu de un singur formular universal.

🔶 2. Tipurile de documente care trebuie implementate (conform legislației + practică)
✔ 1. Proces verbal de recepție lucrări
Obligatoriu în construcții.
Câmpuri:

comisie recepție

data recepției

obiectiv

constatări

defecte

concluzie: admis / respins

semnături

✔ 2. Proces verbal de lucrări ascunse
Obligatoriu înainte de acoperirea lucrărilor.
Câmpuri:

descriere lucrări ascunse

verificări efectuate

responsabil tehnic

fotografii

semnături

✔ 3. Proces verbal de predare-primire
Câmpuri:

predat de

primit de

obiecte / materiale / echipamente

stare

semnături

✔ 4. Proces verbal de remediere defecte
Câmpuri:

defect identificat

responsabil remediere

termen

confirmare remediere

semnături

✔ 5. Proces verbal de constatare
Câmpuri:

situație constatată

martori

fotografii

măsuri

✔ 6. Contract de prestări servicii
Câmpuri:

părți contractante

obiect contract

valoare

termene

penalități

semnături

✔ 7. Ofertă comercială
Câmpuri:

client

descriere lucrări

prețuri

termene

valabilitate ofertă

✔ 8. Deviz
Câmpuri:

materiale

manoperă

utilaje

totaluri

TVA

semnături

✔ 9. Factură
Câmpuri:

emitent

beneficiar

produse/servicii

cantități

prețuri

TVA

total

scadență

✔ 10. Aviz de însoțire marfă
Câmpuri:

furnizor

beneficiar

materiale

cantități

transportator

🔶 3. Arhitectura completă a modulului Documente Tipizate
🔸 A. Tabel nou: document_types
Conține:

id

cod (ex: pv_receptie, pv_ascunse, contract, deviz)

nume

descriere

categorie (calitate, financiar, legal)

sablon_pdf (fișier Blade)

sablon_form (fișier Vue)

🔸 B. Tabel: document_fields
Conține:

id

document_type_id

nume_camp

tip (text, number, date, textarea, select, file)

obligatoriu (bool)

ordine

🔸 C. Tabel: document_instances
Conține:

id

project_id

document_type_id

status (draft, aprobat, respins)

json_fields (valorile completate)

pdf_path

created_by

approved_by

🔶 4. UI/UX final (cum trebuie să arate în Modulia)
În meniul Documente:
Registru documente

Procese verbale

Contracte

Oferte

Devize

Facturi

Avize

Configurare documente

Când creezi un document:
Alegi tipul documentului

Formularul se schimbă automat

Câmpurile sunt tipizate

Layout-ul PDF este specific

Poți aproba documentul

Poți trimite documentul clientului

Poți exporta PDF/XLSX

🔶 5. Legătura cu planurile de facturare (foarte important)
Plan Brand de bază:
1 sablon per tip de document

Plan Brand complet:
sabloane multiple

antet + footer custom

culori custom

fonturi custom

Plan Enterprise:
white-label complet

sabloane nelimitate

aprobări documente

domeniu propriu

1. PROCESE VERBALE (PV)
1.1. PV de recepție lucrări (obligatoriu legal)
Scop:
Confirmarea finală a lucrărilor executate.

Câmpuri obligatorii:
Comisia de recepție (nume + funcție)

Data recepției

Obiectiv / lucrare

Descriere lucrări recepționate

Defecte constatate

Concluzie: Admis / Respins

Semnături (minim 3 membri)

Opționale:
Fotografii

Observații suplimentare

Layout PDF:
antet oficial

tabel comisie

secțiune defecte

secțiune concluzie

semnături

Flux aprobare:
Responsabil tehnic → aprobare

Manager proiect → confirmare finală

1.2. PV lucrări ascunse (obligatoriu legal)
Scop:
Confirmarea lucrărilor înainte de acoperire.

Câmpuri obligatorii:
Descriere lucrări ascunse

Verificări efectuate

Responsabil tehnic

Data verificării

Semnături

Opționale:
Fotografii

Materiale folosite

Layout PDF:
secțiune tehnică

secțiune verificări

secțiune semnături

Flux aprobare:
Responsabil tehnic

Diriginte de șantier

1.3. PV predare‑primire
Scop:
Transferul responsabilității între echipe / subcontractori.

Câmpuri obligatorii:
Predat de / primit de

Obiecte / materiale / echipamente

Stare

Data

Semnături

Layout PDF:
tabel inventar

secțiune stare

semnături

1.4. PV remediere defecte
Scop:
Confirmarea remedierii defectelor.

Câmpuri obligatorii:
Defect identificat

Responsabil remediere

Termen

Confirmare remediere

Semnături

Layout PDF:
tabel defecte

secțiune remediere

semnături

1.5. PV constatare
Scop:
Documentarea unei situații neprevăzute.

Câmpuri obligatorii:
Situație constatată

Martori

Data

Semnături

Opționale:
Fotografii

Măsuri recomandate

🟧 2. DOCUMENTE FINANCIARE
2.1. Factură
Câmpuri obligatorii:
Emitent (firma)

Beneficiar

Produse / servicii

Cantități

Preț unitar

TVA

Total

Scadență

Serie + număr factură

Layout PDF:
layout fiscal standard

tabel detalii

totaluri

semnături opționale

2.2. Aviz de însoțire marfă
Câmpuri obligatorii:
Furnizor

Beneficiar

Materiale

Cantități

Transportator

Data

Layout PDF:
tabel materiale

secțiune transport

semnături

🟧 3. DOCUMENTE COMERCIALE
3.1. Ofertă comercială
Câmpuri obligatorii:
Client

Descriere lucrări

Prețuri

Termene

Valabilitate ofertă

Layout PDF:
layout modern

secțiune prețuri

secțiune termene

secțiune condiții

3.2. Deviz
Câmpuri obligatorii:
Materiale

Manoperă

Utilaje

Totaluri

TVA

Layout PDF:
tabel detaliat

secțiune totaluri

semnături opționale

🟧 4. DOCUMENTE CONTRACTUALE
4.1. Contract prestări servicii
Câmpuri obligatorii:
Părți contractante

Obiect contract

Valoare

Termene

Penalități

Semnături

Layout PDF:
layout legal

secțiune clauze

secțiune semnături

🟧 5. ARHITECTURA TEHNICĂ (backend)
Tabel: document_types
id

cod

nume

categorie

sablon_pdf

sablon_form

Tabel: document_fields
id

document_type_id

nume

tip

obligatoriu

ordine

Tabel: document_instances
id

project_id

document_type_id

status

json_fields

pdf_path

created_by

approved_by

🟧 6. UI/UX FINAL
În meniul Documente:
Registru documente

Procese verbale

Contracte

Oferte

Devize

Facturi

Avize

Configurare documente

La creare document:
Alegi tipul

Formularul se schimbă automat

Câmpurile sunt tipizate

PDF-ul este specific

Poți aproba documentul

Poți trimite documentul

🟧 7. Integrare cu planurile de facturare
Brand de bază:
1 sablon per tip

Brand complet:
sabloane multiple

antet + footer custom

culori custom

fonturi custom

Enterprise:
white‑label complet

sabloane nelimitate

aprobări documente

domeniu propriu