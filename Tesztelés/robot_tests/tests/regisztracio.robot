*** Settings ***
Documentation       Regisztráció tesztelése
Library             SeleniumLibrary
Resource            ../resources/locators.resource

*** Variables ***
${SIGNUP URL}        http://localhost:5173/register
${BROWSER}          chrome


${VALID_FIRSTNAME}      Imre
${VALID_LASTNAME}      Szabó
${VALID_THIRDNAME}     Dávid
${VALID_EMAIL}      szabo.imre@iskola.hu
${VALID_PASS}       szabo123
${VALID_STREET}     Szabadság utca 10

${INVALID_FIRSTNAME}      Imre
${INVALID_LASTNAME}       Szabó
${INVALID_THIRDNAME}      John
${INVALID_EMAIL}      szabo@gmail.com
${INVALID_PASS}       szabo

${SIGNUP_FIRSTNAME_INPUT}             //*[@id="app"]/main/div/div[2]/div/form/div[1]/input[1]
${SIGNUP_LASTNAME_INPUT}             //*[@id="app"]/main/div/div[2]/div/form/div[1]/input[2]
${SIGNUP_THIRDNAME_INPUT}             //*[@id="app"]/main/div/div[2]/div/form/div[1]/input[3]
${SIGNUP_SELECT_COUNTY}               //*[@id="app"]/main/div/div[2]/div/form/div[2]/div[1]/select
${SIGNUP_SELECT_CITY}               //*[@id="app"]/main/div/div[2]/div/form/div[2]/div[2]/select
${SIGNUP_STREET_INPUT}               //*[@id="app"]/main/div/div[2]/div/form/div[3]/input
${SIGNUP_EMAIL_INPUT}                 //*[@id="app"]/main/div/div[2]/div/form/div[4]/input
${SIGNUP_PASSWORD_INPUT}             //*[@id="app"]/main/div/div[2]/div/form/div[5]/div[1]/input
${SIGNUP_PASSWORD_AGAIN_INPUT}        //*[@id="app"]/main/div/div[2]/div/form/div[5]/div[2]/input
${SIGNUP_USERTYPE_SELECT}            //*[@id="app"]/main/div/div[2]/div/form/div[6]/select
${SIGNUP_BUTTON}                 //*[@id="app"]/main/div/div[2]/div/form/button





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
    #Input Text          ${SIGNUP_THIRDNAME_INPUT}    ${VALID_THIRDNAME}
    Click Element       ${SIGNUP_SELECT_COUNTY} 
    Sleep    2s
    Select From List By Value    ${SIGNUP_SELECT_COUNTY}    1 
    Click Element       ${SIGNUP_SELECT_CITY}
    Sleep    2s
    Select From List By Value    ${SIGNUP_SELECT_CITY}    1
    Sleep    2s
    Input Text          ${SIGNUP_STREET_INPUT}    ${VALID_STREET}
    Input Text          ${SIGNUP_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Password      ${SIGNUP_PASSWORD_INPUT}    ${VALID_PASS}
    Input Password      ${SIGNUP_PASSWORD_AGAIN_INPUT}    ${VALID_PASS}

    Click Button        ${SIGNUP_BUTTON}

    Sleep    2s
    
    # Alert kezelése
    ${alert_text}=    Handle Alert    ACCEPT    timeout=5s
    Log    Alert szövege: ${alert_text}
    
    # Alert a várt szöveget tartalmazza-e?
    Should Contain    ${alert_text}    Sikeres regisztráció!    
    

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
    #Input Text          ${SIGNUP_THIRDNAME_INPUT}    ${INVALID_THIRDNAME}
    Click Element       ${SIGNUP_SELECT_COUNTY}
    Sleep    2s
    Select From List By Value    ${SIGNUP_SELECT_COUNTY}    1
    Click Element       ${SIGNUP_SELECT_CITY}
    Sleep    2s
    Select From List By Value    ${SIGNUP_SELECT_CITY}    1
    Input Text          ${SIGNUP_STREET_INPUT}    ${VALID_STREET}
    Input Text          ${SIGNUP_EMAIL_INPUT}    ${INVALID_EMAIL}
    Input Password      ${SIGNUP_PASSWORD_INPUT}    ${INVALID_PASS}
    Input Password      ${SIGNUP_PASSWORD_AGAIN_INPUT}    ${INVALID_PASS}

    Click Button        ${SIGNUP_BUTTON}

    Sleep    2s
    Capture Page Screenshot    

    
    [Teardown]    Close Browser