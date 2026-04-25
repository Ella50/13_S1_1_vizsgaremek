*** Settings ***
Documentation       Rendelés tesztelése
Library             SeleniumLibrary
Resource            ../resources/locators.resource

*** Variables ***
${LOGIN URL}        http://localhost:5173/login
${BROWSER}          chrome

${LOGIN_EMAIL_INPUT}             //*[@id="app"]/main/div[1]/div[2]/div/form/input
${LOGIN_PASSWORD_INPUT}          //*[@id="app"]/main/div[1]/div[2]/div/form/div/input
${LOGIN_BUTTON}                 //*[@id="app"]/main/div[1]/div[2]/div/form/button

${VALID_EMAIL}      kovacs.janos@iskola.hu
${VALID_PASS}       tanar123


${MEAL_NAME}    A teszt étel
${MEAL_ING_1}    Búzaliszt
${MEAL_ING_2}    Tojás
${MEAL_ING_3}    Tej
${MEAL_AMOUNT_1}    100
${MEAL_AMOUNT_2}    2
${MEAL_AMOUNT_3}    200
${MEAL_DESC}    Ez egy teszt étel leírása.


${ORDERS_BUTTON}         //*[@id="app"]/main/div[1]/div/div[2]/div/div/a[4]
${NEXT_MONTH}         //*[@id="app"]/main/div[1]/div/div[1]/div/div/button[3]
${CHOSEN_OPTION_BUTTON}          //*[@id="app"]/main/div[1]/div/div[2]/table/tbody/tr[1]/td[5]/div/button[1]
${PLACE_ORDER_BUTTON}    //*[@id="app"]/main/div[1]/div/div[2]/table/tbody/tr[1]/td[7]/div/button

${OK_BUTTON}         //*[@id="app"]/main/div[1]/div[2]/div/div/button[2]



    
*** Test Cases ***
Sikeres ételkészítés
    [Documentation] 
    
    Open Browser        ${LOGIN URL}    ${BROWSER}
    Maximize Browser Window
    

    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s   

    Input Text          ${LOGIN_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Text          ${LOGIN_PASSWORD_INPUT}    ${VALID_PASS}
    Click Button        ${LOGIN_BUTTON}
    
    Wait Until Location Contains    dashboard    timeout=15s
    Sleep    2s    


    Click Element       ${ORDERS_BUTTON}
    Sleep    5s


    Click Element       ${NEXT_MONTH}
    Sleep    2s
   
    Click Element       ${CHOSEN_OPTION_BUTTON}
    Sleep    2s

    Click Element   ${PLACE_ORDER_BUTTON}
  

    Sleep    2s
    

    Sleep   5s

    Click Element   ${OK_BUTTON}

    Sleep   5s

    Capture Page Screenshot  sikeres_rendeles.png

    Sleep   5s

    [Teardown]    Close Browser
