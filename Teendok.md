## Egyéb:
- menüben legyen logo, hamburger menü
- ha user felfüggesztve, akkor a kártya törlésre kerüljön
- custom alert
- reszponziv, formazas mindenhol, telefonos nézet
- elfelejtett jelszó állításnál a frontend szétcsúszva és a navbar ne legyen ott
- GOMBOK MINDENHOL
- TELEFONOS NÉZET MINDELHOL (pl. meals.vue tápértékek)
- regisztráció nem jó
- jelszavak: speciális karakterek
- nem

## PersonalInvoices.vue
- gombok működjenek (modal overlay nem megfelelő)
- pdf formázás eltérő az adminostól (jobb??)
- frontend
- ha admin beállítja hogy "fizetv"e, akkor a usernek is kiírja hogy befizetve amúgy meg "Folyamatban.."

## AdminInvoice.vue
- hiba a fizetettre állításkor


## Users.vue
- kártya törlés gomb
- nem tölti be a várost csak a megyét

## Meals.vue
- pontosabb tápérték számítás??
- ha cukorbeteg akkor az adatok nem pontosak figyelmeztetés


## Ingredients.vue
- nem menti az allergént ha új hozzávalót töltök fel

## Orders.vue



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
