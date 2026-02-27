*** Settings ***
Documentation       Új étel létrehozása
Library             SeleniumLibrary
Resource            ../resources/locators.resource

*** Variables ***
${LOGIN URL}        http://localhost:5173/login
${BROWSER}          chrome
${VALID_EMAIL}      admin@iskola.hu
${VALID_PASS}       admin123
${MEAL_NAME}    A teszt étel
${MEAL_ING_1}    Búzaliszt
${MEAL_ING_2}    Tojás
${MEAL_ING_3}    Tej
${MEAL_AMOUNT_1}    100
${MEAL_AMOUNT_2}    2
${MEAL_AMOUNT_3}    200
${MEAL_DESC}    Ez egy teszt étel leírása.

${NEWMEAL_EMAIL_INPUT}             //*[@id="app"]/main/div/div[2]/div/form/input[1]
${NEWMEAL_PASSWORD_INPUT}          //*[@id="app"]/main/div/div[2]/div/form/input[2]
${NEWMEAL_BUTTON}                 //*[@id="app"]/main/div/div[2]/div/form/button
${NEWMEAL_CLICK_MEALS}             //*[@id="app"]/main/div/div/div/a[2]
${NEWMEAL_CLICK_ADDNEWMEALS}            //*[@id="app"]/main/div/div[1]/button
${NEWMEAL_NAME_INPUT}    //*[@id="app"]/main/div/div[4]/div/form/div[1]/input
${NEWMEAL_SELECT_MEALTYPE}    //*[@id="app"]/main/div/div[4]/div/form/div[2]/select
${NEWMEAL_MEAL_DESCRIPTION_INPUT}    //*[@id="app"]/main/div/div[4]/div/form/div[3]/textarea
${NEWMEAL_INGREDIENTS_ADD}    //*[@id="app"]/main/div/div[4]/div/form/div[4]/div[2]/div/div/div[1]/div/input
${NEWMEAL_INGREDIENTS_AMOUNT}    //*[@id="app"]/main/div/div[4]/div/form/div[4]/div[2]/div/div/div[2]/input
${NEWMEAL_INGREDIENTS_TYPE_OF_MESAURE}    //*[@id="app"]/main/div/div[4]/div/form/div[4]/div[2]/div/div/div[3]/select
${MEAL_INGREDIENTS_ADD_BUTTON}    //*[@id="app"]/main/div/div[4]/div/form/div[4]/div[2]/div/div/div[4]/button
${NEWMEAL_INGREDIENTS_SAVE_BUTTON}    //*[@id="app"]/main/div/div[4]/div/form/div[5]/button[2]
${NEWMEAL_ELEMENT}=    Get WebElement    ${MENUMAKER_SAVE_BUTTON}
${NEWMEAL_MODAL}=    Get WebElement    //*[@id="app"]/main/div/div[4]/div
${NEWMEAL_TYPE_SELECT_3}=    //*[@id="app"]/main/div/div[4]/div/form/div[4]/div[2]/div/div[1]/div[1]/div/div/div[2]
${NEWMEAL_TYPE_SELECT_2}=    //*[@id="app"]/main/div/div[4]/div/form/div[4]/div[2]/div/div[1]/div[1]/div/div/div
${NEWMEAL_TYPE_SELECT_1}=    //*[@id="app"]/main/div/div[4]/div/form/div[4]/div[2]/div/div[1]/div[1]/div/div/div




    
*** Test Cases ***
Sikeres ételkészítés
    [Documentation]    Sikeres menükészítés még nem létező napra
    
    #Chrome
    Open Browser        ${LOGIN URL}    ${BROWSER}
    Maximize Browser Window
    
    #Weboldal betöltése
    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s    # Adjunk időt a Vue komponenseknek
    
    #Input mezők betöltése
    Wait Until Page Contains Element    ${NEWMEAL_EMAIL_INPUT}    timeout=10s
    Wait Until Page Contains Element    ${NEWMEAL_PASSWORD_INPUT}    timeout=10s
    
    #Bejelentkezés
    Input Text          ${NEWMEAL_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Text          ${NEWMEAL_PASSWORD_INPUT}    ${VALID_PASS}
    Click Button        ${NEWMEAL_BUTTON}
    
    #Dashboard betöltése
    Wait Until Location Contains    dashboard    timeout=15s
    Sleep    2s    

    #Menü készítő oldal betöltése
    Click Element       ${NEWMEAL_CLICK_MEALS}
    Sleep    2s

    #MenuMaker oldal műveletek
    Click Element       ${NEWMEAL_CLICK_ADDNEWMEALS}
    Sleep    2s
    Input Text          ${NEWMEAL_NAME_INPUT}    ${MEAL_NAME}
    Sleep    2s
    Click Element       ${NEWMEAL_SELECT_MEALTYPE}
    Sleep    2s
    Select From List By Index    ${NEWMEAL_SELECT_MEALTYPE}    2

    #Étel hozzávalók és emmnyiségek megadása
    Input Text          ${NEWMEAL_MEAL_DESCRIPTION_INPUT}    ${MEAL_DESC}
    Input Text          ${NEWMEAL_INGREDIENTS_ADD}    ${MEAL_ING_1}
    Click Element       ${NEWMEAL_TYPE_SELECT_1}
    Input Text          ${NEWMEAL_INGREDIENTS_AMOUNT}    ${MEAL_AMOUNT_1}
    Click Element       ${NEWMEAL_INGREDIENTS_TYPE_OF_MESAURE}
    Select From List By Index    ${NEWMEAL_INGREDIENTS_TYPE_OF_MESAURE}    0
    Click Element       ${MEAL_INGREDIENTS_ADD_BUTTON}
    Sleep    2s
    Input Text          ${NEWMEAL_INGREDIENTS_ADD}    ${MEAL_ING_2}
    Click Element       ${NEWMEAL_TYPE_SELECT_2}
    Input Text          ${NEWMEAL_INGREDIENTS_AMOUNT}    ${MEAL_AMOUNT_2}
    Click Element       ${NEWMEAL_INGREDIENTS_TYPE_OF_MESAURE}
    Select From List By Index    ${NEWMEAL_INGREDIENTS_TYPE_OF_MESAURE}    4
    Click Element       ${MEAL_INGREDIENTS_ADD_BUTTON}
    Sleep    2s
    Input Text          ${NEWMEAL_INGREDIENTS_ADD}    ${MEAL_ING_3}
    Click Element       ${NEWMEAL_TYPE_SELECT_3}
    Input Text          ${NEWMEAL_INGREDIENTS_AMOUNT}    ${MEAL_AMOUNT_3}
    Click Element       ${NEWMEAL_INGREDIENTS_TYPE_OF_MESAURE}
    Select From List By Index    ${NEWMEAL_INGREDIENTS_TYPE_OF_MESAURE}    2
    Click Element       ${MEAL_INGREDIENTS_ADD_BUTTON}
    Execute JavaScript    document.querySelector('.btn-save').scrollIntoView()
    Sleep    5s
    Click Element       ${NEWMEAL_INGREDIENTS_SAVE_BUTTON}
    
    Sleep    5s
    Capture Page Screenshot  sikeres_ujetel.png
    [Teardown]    Close Browser
