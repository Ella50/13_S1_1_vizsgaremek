*** Settings ***
Documentation       Menü szerkesztés tesztelése 
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


${MENUMAKER_BUTTON}                //*[@id="app"]/main/div[1]/div/div[2]/div[1]/div/a[3]

${MENUMAKER_CLICK_EDIT}           //*[@id="app"]/main/div[1]/div/div[2]/div/table/tbody/tr[1]/td[6]/div/button[1]

#${MENUMAKER_CONTINUE_BUTTON}    //*[@id="app"]/main/div/div[2]/div/div/button[1]
#${MENUMAKER_ADD_SOUP}    //*[@id="app"]/main/div/div[2]/div/div/div[2]/div[1]/button
#${MENUMAKER_SELECT_OPA}    //*[@id="app"]/main/div/div[2]/div/div/div[1]/button[2]
#${MENUMAKER_ADD_OPA}    //*[@id="app"]/main/div/div[2]/div/div/div[2]/div[2]/button
#${MENUMAKER_SELECT_OPB}    //*[@id="app"]/main/div/div[2]/div/div/div[1]/button[3]

${MENUMAKER_SELECT_OTHERS}    //*[@id="app"]/main/div[1]/div[2]/div/div[2]/form/div[2]/div[1]/button[4]
${MENUMAKER_ADD_OTHERS}    //*[@id="app"]/main/div[1]/div[2]/div/div[2]/form/div[2]/div[3]/div[1]/button

${MENUMAKER_SAVE_BUTTON}    //*[@id="app"]/main/div[1]/div[2]/div/div[2]/form/div[3]/button[2]

${OK_BUTTON}    //*[@id="app"]/main/div[3]/div/div/button[2]

    
*** Test Cases ***
Sikeres menüszerkesztés
    [Documentation]    Sikeres menüszerkesztés
    
     Open Browser        ${LOGIN URL}    ${BROWSER}
    Maximize Browser Window
    

    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s   

    Input Text          ${LOGIN_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Text          ${LOGIN_PASSWORD_INPUT}    ${VALID_PASS}
    Click Button        ${LOGIN_BUTTON}
    

  
    Wait Until Location Contains    dashboard    timeout=15s
    Sleep    2s    


    Click Element       ${MENUMAKER_BUTTON}
    Sleep    2s

    #MenuMaker oldal műveletek
    Click Element       ${MENUMAKER_CLICK_EDIT}
    Sleep    2s
    Click Element       ${MENUMAKER_SELECT_OTHERS}
    Sleep    2s

    Click Element       ${MENUMAKER_ADD_OTHERS}
    Sleep    2s

    Click Element       ${MENUMAKER_SAVE_BUTTON}

    Sleep    2s
    Click Element       ${OK_BUTTON}

    
    Sleep    2s
    Capture Page Screenshot  sikeres_menuszerkesztes.png

    [Teardown]    Close Browser
