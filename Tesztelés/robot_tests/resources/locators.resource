*** Settings ***
Documentation       Ebben a fájlban tároljuk a weboldalon található elemek azonosítóit.

*** Variables ***
# Bejelentkezési űrlap
${LOGIN_EMAIL_INPUT}        id=email
${LOGIN_PASSWORD_INPUT}     id=password
${LOGIN_BUTTON}             css=button[type="submit"]
${LOGIN_ERROR_MESSAGE}      css=.alert.alert-danger