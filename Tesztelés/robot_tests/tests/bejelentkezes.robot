*** Settings ***
Documentation       Bejelentkezés tesztelése
Library             SeleniumLibrary
Resource            ../resources/locators.resource

*** Variables ***
${LOGIN URL}        http://localhost:5173/login
${BROWSER}          chrome
${VALID_EMAIL}      admin@iskola.hu
${VALID_PASS}       admin123
${INVALID_EMAIL}      szabo.david@iskola.hu
${INVALID_PASS}       tegla

${LOGIN_EMAIL_INPUT}             //*[@id="app"]/main/div/div[2]/div/form/input[1]
${LOGIN_PASSWORD_INPUT}          //*[@id="app"]/main/div/div[2]/div/form/input[2]
${LOGIN_BUTTON}                 //*[@id="app"]/main/div/div[2]/div/form/button
${LOGOUT_BUTTON}                //*[@id="logout"]




*** Test Cases ***
Sikeres bejelentkezés
    [Documentation]    Érvényes adatokkal való bejelentkezés
    
    #Chrome
    Open Browser        ${LOGIN URL}    ${BROWSER}
    Maximize Browser Window
    
    #Weboldal betöltése
    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s    # Adjunk időt a Vue komponenseknek
    
    #Input mezők betöltése
    Wait Until Page Contains Element    ${LOGIN_EMAIL_INPUT}    timeout=10s
    Wait Until Page Contains Element    ${LOGIN_PASSWORD_INPUT}    timeout=10s
    
    #Bejelentkezés
    Input Text          ${LOGIN_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Text          ${LOGIN_PASSWORD_INPUT}    ${VALID_PASS}
    Click Button        ${LOGIN_BUTTON}
    
    #Dashboard betöltése
    Wait Until Location Contains    dashboard    timeout=15s
    Sleep    2s    

    #Kijelentkezés
    Click Button        ${LOGOUT_BUTTON}
    
    Capture Page Screenshot
    [Teardown]    Close Browser


Sikertelen bejelentkezés
    [Documentation]    Érvénytelen adatokkal való bejelentkezési kísérlet
    
    Open Browser        ${LOGIN URL}    ${BROWSER}
    Maximize Browser Window
    
    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s 
    
    Wait Until Page Contains Element    ${LOGIN_EMAIL_INPUT}    timeout=10s
    Wait Until Page Contains Element    ${LOGIN_PASSWORD_INPUT}    timeout=10s
    
    Input Text          ${LOGIN_EMAIL_INPUT}    ${INVALID_EMAIL}
    Input Text          ${LOGIN_PASSWORD_INPUT}    ${INVALID_PASS}
    Click Button        ${LOGIN_BUTTON}

    Sleep    2s    
    
    Capture Page Screenshot
    [Teardown]    Close Browser