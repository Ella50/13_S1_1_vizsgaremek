*** Settings ***
Documentation       Hozzávaló törlés
Library             SeleniumLibrary
Resource            ../resources/locators.resource

*** Variables ***
${LOGIN URL}        http://localhost:5173/login
${BROWSER}          chrome

${LOGIN_EMAIL_INPUT}             //*[@id="app"]/main/div[1]/div[2]/div/form/input
${LOGIN_PASSWORD_INPUT}          //*[@id="app"]/main/div[1]/div[2]/div/form/div/input
${LOGIN_BUTTON}                 //*[@id="app"]/main/div[1]/div[2]/div/form/button

${VALID_EMAIL}      farkas.ilona@iskola.hu
${VALID_PASS}       konyha123

${SEARCH_INPUT}       Töröl

${ING_BUTTON}         //*[@id="app"]/main/div[1]/div/div[2]/div[1]/div/a[2]

${SEARCH}       //*[@id="app"]/main/div[1]/div/div[1]/div/div/input

${DELETE_BUTTON}          //*[@id="app"]/main/div[1]/div/div[2]/div[1]/table/tbody/tr/td[5]/div/button[3]

${CONFIRM_DELETE}       //*[@id="app"]/main/div[1]/div[2]/div/div[2]/div/button[2]



    
*** Test Cases ***
Sikeres hozzávaló törlés
    [Documentation] 
    
    Open Browser        ${LOGIN URL}    ${BROWSER}
    Maximize Browser Window
    

    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s   

    Input Text          ${LOGIN_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Text          ${LOGIN_PASSWORD_INPUT}    ${VALID_PASS}
    Click Button        ${LOGIN_BUTTON}
    

    #Dashboard betöltése
    Wait Until Location Contains    dashboard    timeout=15s
    Sleep    2s    

  

    Click Element       ${ING_BUTTON}
    Sleep    5s

    Input Text          ${SEARCH}    ${SEARCH_INPUT}
    Sleep    2s

 
    Click Element       ${DELETE_BUTTON}

    Sleep   2s

    Click Element       ${CONFIRM_DELETE}

    Sleep    5s
    Capture Page Screenshot  sikeres_hozzavalotorles.png
    [Teardown]    Close Browser
