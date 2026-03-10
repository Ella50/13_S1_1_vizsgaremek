## Egyéb:
- több allergén kérvény????????
- számla (adminnak és usereknek is)
- class???
- menüben legyen logo, hamburger menü
- ha vannak az ételeknél képek, akkor lehessen feltölteni képet az új étel hozzáadásánál - NEM LESZNEK
- ha user felfüggesztve, akkor a kártya törlésre kerüljön



## Login.vue
- jelszó elfelejtve

## Users.vue
- kártya törlés gomb

## Meals.vue
- pontosabb tápérték számítás
- ha cukorbeteg akkor az adatok nem pontosak figyelmeztetés


## Ingredient.vue
- amikor hozzáadunk egy összetevőt a listához, akkor az allergénjeit is be lehessen állítani


## Orders.vue
- a cukormentes rendelések összessége is meg legyen jelenítve (A opcióból készül a ckormentes opció) + a táblázatban is


## MenuMaker.vue
- frontend üres 


## Userprofil.vue
- jelszó változtatás
- kedvezményes vagy normál ár
- cukorbeteg-e disabled -> kérvényezés az adminnak
- lakcím változtatás


## TodayMenu.vue
- frontend


## PersonalOrders.vue
- nincs még kész
- cukormentes opció az A-ból
- lemondás, rendelés, módosítás
- figyelmeztetés allergénre (amilye van a felhasználónak) (pirossal írva az étel neve)



## Teszt(robotframework)
-Dokumentáció: táblázat(log) + képernyőképek + teszt adatok kiírása

(
    pip install robotframework-seleniumlibrary
    Robotframework használat: python -m robot --outputdir results tests\bejelentkezes.robot
)

## Domi
-Price id a seederben
-számlázás(date? 2026-03, de az adatb-ben 2026-03-01 a billingMonth)
