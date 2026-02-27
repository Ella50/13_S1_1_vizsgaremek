*** Settings ***
Documentation       Menükészítő tesztelése
Library             SeleniumLibrary
Resource            ../resources/locators.resource

*** Variables ***
${LOGIN URL}        http://localhost:5173/login
${BROWSER}          chrome
${VALID_EMAIL}      admin@iskola.hu
${VALID_PASS}       admin123

${MENUMAKER_EMAIL_INPUT}             //*[@id="app"]/main/div/div[2]/div/form/input[1]
${MENUMAKER_PASSWORD_INPUT}          //*[@id="app"]/main/div/div[2]/div/form/input[2]
${MENUMAKER_BUTTON}                 //*[@id="app"]/main/div/div[2]/div/form/button
${MENUMAKER_CLICK_MENU}             //*[@id="app"]/main/div/div/div/a[4]
${MENUMAKER_CLICK_ADDNEW}            //*[@id="app"]/main/div/div/button[1]
${MENUMAKER_SELECT_DATE_ADDNEW}    //*[@id="app"]/main/div/div[2]/div/div/select
${MENUMAKER_CONTINUE_BUTTON}    //*[@id="app"]/main/div/div[2]/div/div/button[1]
${MENUMAKER_ADD_SOUP}    //*[@id="app"]/main/div/div[2]/div/div/div[2]/div[1]/button
${MENUMAKER_SELECT_OPA}    //*[@id="app"]/main/div/div[2]/div/div/div[1]/button[2]
${MENUMAKER_ADD_OPA}    //*[@id="app"]/main/div/div[2]/div/div/div[2]/div[2]/button
${MENUMAKER_SELECT_OPB}    //*[@id="app"]/main/div/div[2]/div/div/div[1]/button[3]
${MENUMAKER_ADD_OPB}    //*[@id="app"]/main/div/div[2]/div/div/div[2]/div[5]/button
${MENUMAKER_SELECT_OTHERS}    //*[@id="app"]/main/div/div[2]/div/div/div[1]/button[4]
${MENUMAKER_ADD_OTHERS}    //*[@id="app"]/main/div/div[2]/div/div/div[2]/div[1]/button
${MENUMAKER_SAVE_BUTTON}    //*[@id="app"]/main/div/div[2]/div/div/div[3]/button[1]
${MENUMAKER_ELEMENT}=    Get WebElement    ${MENUMAKER_SAVE_BUTTON}
${MENUMAKER_MODAL}=    Get WebElement    //*[@id="app"]/main/div/div[4]/div




    
*** Test Cases ***
Sikeres menükészítés
    [Documentation]    Sikeres menükészítés még nem létező napra
    
    #Chrome
    Open Browser        ${LOGIN URL}    ${BROWSER}
    Maximize Browser Window
    
    #Weboldal betöltése
    Wait Until Page Contains Element    css=#app    timeout=10s
    Sleep    2s    # Adjunk időt a Vue komponenseknek
    
    #Input mezők betöltése
    Wait Until Page Contains Element    ${MENUMAKER_EMAIL_INPUT}    timeout=10s
    Wait Until Page Contains Element    ${MENUMAKER_PASSWORD_INPUT}    timeout=10s
    
    #Bejelentkezés
    Input Text          ${MENUMAKER_EMAIL_INPUT}    ${VALID_EMAIL}
    Input Text          ${MENUMAKER_PASSWORD_INPUT}    ${VALID_PASS}
    Click Button        ${MENUMAKER_BUTTON}
    
    #Dashboard betöltése
    Wait Until Location Contains    dashboard    timeout=15s
    Sleep    2s    

    #Menü készítő oldal betöltése
    Click Element       ${MENUMAKER_CLICK_MENU}
    Sleep    2s

    #MenuMaker oldal műveletek
    Click Element       ${MENUMAKER_CLICK_ADDNEW}
    Sleep    2s
    Click Element       ${MENUMAKER_SELECT_DATE_ADDNEW}
    Sleep    2s
    Select From List By Index    ${MENUMAKER_SELECT_DATE_ADDNEW}    1
    Click Button        ${MENUMAKER_CONTINUE_BUTTON}
    Sleep    2s
    Click Element       ${MENUMAKER_ADD_SOUP}
    Click Element       ${MENUMAKER_SELECT_OPA}
    Click Element       ${MENUMAKER_ADD_OPA}
    Click Element       ${MENUMAKER_SELECT_OPB}
    Click Element       ${MENUMAKER_ADD_OPB}
    Click Element       ${MENUMAKER_SELECT_OTHERS}
    Click Element       ${MENUMAKER_ADD_OTHERS}
    Sleep    2s
    Click Element       ${MENUMAKER_SAVE_BUTTON}

    # Alert kezelése
    #Wait Until Page Contains    Az új menü sikeresen létre lett hozva.    timeout=10s
    
    Sleep    2s
    Capture Page Screenshot  
    [Teardown]    Close Browser
