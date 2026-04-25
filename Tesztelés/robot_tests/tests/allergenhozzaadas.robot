*** Settings ***
Documentation       Személyes allergének beállítása
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



${PROFILE_BUTTON}        //*[@id="app"]/nav/div[1]/div/a[4]

${SELECT_ALLERGEN}         //*[@id="app"]/main/div[1]/div/div[2]/div/div[2]/div[1]/div[2]/div[2]/div[2]/div/select
${ADD_ALLERGEN_BUTTON}          //*[@id="app"]/main/div[1]/div/div[2]/div/div[2]/div[1]/div[2]/div[2]/div[2]/div/button

${SUCCESS_ALERT}         /div/div



    
*** Test Cases ***
Sikeres allergén hozzaadás
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


    Click Element       ${PROFILE_BUTTON}
    Sleep    15s


    Click Element       ${SELECT_ALLERGEN} 
    Sleep   2s
    Select From List By Value    ${SELECT_ALLERGEN}    4
    Sleep    2s
   
    Click Element       ${ADD_ALLERGEN_BUTTON}

    
    Sleep   5s




    Capture Page Screenshot  sikeres_allergenhozzaadas.png



    [Teardown]    Close Browser
