## Egyéb:
- optimalizálás: app.vue-ba table, keresés, modal, allergenBadges, pagináció, datePicker stílusok +  varcolor
- ha user felfüggesztve, akkor a kártya törlésre kerüljön
- custom alert
- TELEFONOS NÉZET MINDELHOL (pl. meals.vue tápértékek)
- jelszavak: speciális karakterek lekezelése?
- ha modal: mögötte ne lehessen görgetni
- felfüggesztett akkor beléphet megnézni a számláit?

## Doucments
- 


## PersonalInvoices.vue
- gombok működjenek (modal overlay nem megfelelő)
- pdf formázás eltérő az adminostól (jobb??)
- frontend
- ha admin beállítja hogy "fizetv"e, akkor a usernek is kiírja hogy befizetve amúgy meg "Folyamatban.."

## AdminInvoice.vue
- hiba a fizetettre állításkor
- legyen keresés/szűrő? 


## Users.vue
- kártya törlés gomb
- nem tölti be a várost csak a megyét
- kicsit elcsúszva a modal

## Meals.vue
- pontosabb tápérték számítás??
- ha cukorbeteg akkor az adatok nem pontosak figyelmeztetés


## Ingredients.vue
- nem menti az allergént ha új hozzávalót töltök fel
- nem lehet törölni ingredientet (újat)
- nem lehet változtatni az elérhetőségét
- modal

## Orders.vue

## Profile.vue
- ha már admin elfogadta dokumentummot akkor is le lehessen tölteni
- értesítés ha nincs elfogadva???


## MenuMaker.vue
- frontend üres
- kicsi káosz


## Userprofil.vue
- frontend
- gombok


## TodayMenu.vue
- frontend
- backend (nem tölti be)


## PersonalOrders.vue
- frontend
- reszponzív legyen
- külön telefonos reszponzivitás??



## Teszt(robotframework)
-Dokumentáció: táblázat(log) + képernyőképek + teszt adatok kiírása

(
    pip install robotframework-seleniumlibrary
    Robotframework használat: python -m robot --outputdir results tests\bejelentkezes.robot
)

## Domi
-Price id a seederben
-számlázás(date? 2026-03, de az adatb-ben 2026-03-01 a billingMonth)
