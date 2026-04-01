## Egyéb:
- optimalizálás: app.vue-ba table, keresés, modal, allergenBadges, pagináció, datePicker stílusok +  varcolor
- ha user felfüggesztve, akkor a kártya törlésre kerüljön
- custom alert
- TELEFONOS NÉZET MINDELHOL (pl. meals.vue tápértékek)
- jelszavak: speciális karakterek lekezelése?
- felfüggesztett akkor beléphet megnézni a számláit?
- regisztráció sikeres üzenet (custom alert legyen)
- jelszó nézés nem teljesen jó

## Doucments
- 

## PersonalOrders.vue
- hónapváálasztó modalon kívűl görgetés

## PersonalInvoices.vue
- gombok működjenek (modal overlay nem megfelelő)
- pdf formázás eltérő az adminostól 
- ha admin beállítja hogy "fizetv"e, akkor a usernek is kiírja hogy befizetve amúgy meg "Folyamatban.."

## AdminInvoice.vue
- hiba a fizetettre állításkor
- legyen keresés/szűrő? 


## Users.vue
- kártya törlés gomb
- kicsit elcsúszva a modal

## Meals.vue
- pontosabb tápérték számítás??
- ha cukorbeteg akkor az adatok nem pontosak figyelmeztetés
- összetevők felsaorolása a usernek
- nem működik a törlés NEM IGAZ -> nem törli mert van hozzá rendelés kapcsolva (ilyne hibaüzenet kell)


## Ingredients.vue
- nem menti az allergént ha új hozzávalót töltök fel
- nem lehet törölni ingredientet (újat)
- nem lehet változtatni az elérhetőségét
- ne lenyíljon, hanem modal

## Orders.vue

## Profile.vue
- értesítés ha nincs elfogadva???


## MenuMaker.vue
- összes menü betöltése?


## Userprofil.vue


## TodayMenu.vue


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
