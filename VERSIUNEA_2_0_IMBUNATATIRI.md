# VERSIUNEA 2.0 - Imbunatatiri Premium

Data: 2026-07-03

## Lot A - Planificare si Resurse

1. Dashboard Resurse
- Overview dedicat pentru:
  - echipe supraincarcate
  - subcontractori in paralel
  - utilaje indisponibile
  - materiale cu stoc scazut

2. Calendar combinat echipe + utilaje
- View unificat pentru planificarea resurselor.

3. Costuri resurse in timp real
- Cost utilaje / zi
- Cost echipe / zi
- Cost subcontractori / etapa

4. Integrare materiale in taskuri
- Taskurile pot inregistra consum materiale (cantitate, UM, pret).

5. Alerte automate operationale
- Exemple:
  - echipa supraincarcata
  - utilaj rezervat in paralel
  - material sub prag minim de stoc
  - subcontractor pe proiecte paralele

## Lot B - Calitate si Defecte

1. Foto pentru defecte (mobil)
- Upload direct din telefon pentru defecte.
- Preview foto in formular si in lista de defecte.

2. Checklist verificari
- Checklist intern pentru verificari complexe de calitate.
- Itemi bifabili in create/edit.

3. Status automat verificari
- Daca toate taskurile etapei sunt inchise, verificarea se marcheaza automat ca finalizata (Conform).

4. Raport PDF calitate
- Export PDF pentru receptii partiale si finale.
- Include: detalii verificare, checklist, observatii si insight AI.

5. Integrare AI in verificari
- Insight-uri automate de tip:
  - "Aceasta etapa are X verificari nefinalizate."

## Validare tehnica

- Migrare baza de date: efectuata cu succes.
- Teste targetate: trecute.
- Build frontend: trecut.

## Observatii

- Pentru upload foto in defecte este recomandat sa existe storage link activ:
  - `php artisan storage:link`
