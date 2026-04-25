*** Settings ***
Documentation       Személyes számla letöltése 
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



${INVOICES_BUTTON}        //*[@id="app"]/nav/div[1]/div/a[6]

${PDF_BUTTON}         //*[@id="app"]/main/div[1]/div/div[2]/div/table/tbody/tr[2]/td[6]/div/button[2]





    
*** Test Cases ***
Sikeres számla letöltés
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


    Click Element       ${INVOICES_BUTTON}
    Sleep    15s



    Capture Page Screenshot  sikeres_szamlaletoltes.png



    [Teardown]    Close Browser
