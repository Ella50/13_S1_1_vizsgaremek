*** Settings ***
Documentation       Regisztráció tesztelése
Library             SeleniumLibrary
Resource            ../resources/locators.resource

*** Variables ***
${SIGNUP URL}        http://localhost:5173/register
${BROWSER}          chrome


${VALID_FIRSTNAME}      Imre
${VALID_LASTNAME}      Szabó
${VALID_EMAIL}      szabo.imre@iskola.hu
${VALID_PASS}       szabo123

${INVALID_FIRSTNAME}      Imre
${INVALID_LASTNAME}       Szabó
${INVALID_EMAIL}      szabo@gmail.com
${INVALID_PASS}       szabo

${SIGNUP_FIRSTNAME_INPUT}             //*[@id="app"]/main/div/div[2]/form/input[1]
${SIGNUP_LASTNAME_INPUT}             //*[@id="app"]/main/div/div[2]/form/input[2]
${SIGNUP_EMAIL_INPUT}                 //*[@id="app"]/main/div/div[2]/form/input[3]
${SIGNUP_PASSWORD_INPUT}             //*[@id="app"]/main/div/div[2]/form/input[4]
${SIGNUP_PASSWORD_AGAIN_INPUT}        //*[@id="app"]/main/div/div[2]/form/input[5]
${SIGNUP_USERTYPE_SELECT}            //*[@id="app"]/main/div/div[2]/form/select
${SIGNUP_BUTTON}                 //*[@id="app"]/main/div/div[2]/form/button





*** Test Cases ***

Sikeres regisztráció
    [Documentation]    Érvényes adatokkal való regisztráció
    
    Open Browser        ${SIGNUP URL}    ${BROWSER}
    Maximize Browser Window
    
    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s 
    
    Wait Until Page Contains Element    ${SIGNUP_EMAIL_INPUT}    timeout=10s
    Wait Until Page Contains Element    ${SIGNUP_PASSWORD_INPUT}    timeout=10s

    Input Text          ${SIGNUP_LASTNAME_INPUT}    ${VALID_LASTNAME}
    Input Text          ${SIGNUP_FIRSTNAME_INPUT}    ${VALID_FIRSTNAME}
    Input Text          ${SIGNUP_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Text          ${SIGNUP_PASSWORD_INPUT}    ${VALID_PASS}
    Input Text          ${SIGNUP_PASSWORD_AGAIN_INPUT}    ${VALID_PASS}

    Click Button        ${SIGNUP_BUTTON}

    Sleep    2s
    
    # Alert kezelése
    ${alert_text}=    Handle Alert    ACCEPT    timeout=5s
    Log    Alert szövege: ${alert_text}
    
    # Alert a várt szöveget tartalmazza-e?
    Should Contain    ${alert_text}    Sikeres regisztáció!
    

    Sleep    2s
    Capture Page Screenshot    
    
    # Átirányított-e a bejelentkezési oldalra
    ${CURRENT_URL}=    Get Location
    Log    Jelenlegi URL: ${CURRENT_URL}
    
    [Teardown]    Close Browser



Sikertelen regisztráció
    [Documentation]    Érvénytelen jelszóval való regisztráció
    
    Open Browser        ${SIGNUP URL}    ${BROWSER}
    Maximize Browser Window
    
    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s 
    
    Wait Until Page Contains Element    ${SIGNUP_EMAIL_INPUT}    timeout=10s
    Wait Until Page Contains Element    ${SIGNUP_PASSWORD_INPUT}    timeout=10s

    Input Text          ${SIGNUP_LASTNAME_INPUT}    ${INVALID_LASTNAME}
    Input Text          ${SIGNUP_FIRSTNAME_INPUT}    ${INVALID_FIRSTNAME}
    Input Text          ${SIGNUP_EMAIL_INPUT}    ${INVALID_EMAIL}
    Input Text          ${SIGNUP_PASSWORD_INPUT}    ${INVALID_PASS}
    Input Text          ${SIGNUP_PASSWORD_AGAIN_INPUT}    ${INVALID_PASS}

    Click Button        ${SIGNUP_BUTTON}

    Sleep    2s
    Capture Page Screenshot    

    
    [Teardown]    Close Browser