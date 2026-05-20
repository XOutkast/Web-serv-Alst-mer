# Reflektion av m4 - PHP

---

## Inledning

Inloggningsapplikation med gästbok, där jag lagar allt data i JSON-filer.
Flera användare kan registrera sig, logga in, hantera sitt konto och skriva i en gästbok också radera och redegira den.


## Funktioner

- **Registrering:** Användare kan skapa konto med unikt användarnamn och lösenord (hashat).
- **Inloggning:** Användare loggar in och får tillgång till adminsidan. "Håll mig inloggad" använder cookie.
- **Skyddade sidor:** check_login.php ser till att bara inloggade användare får åtkomst till admin och gästbok.
- **Gästbok:** Alla inloggade kan skriva, redigera och ta bort sina egna inlägg. Admin kan hantera alla inlägg.
- **Kontohantering:** Byt lösenord och radera konto är möjligt.
- **Roller:** Admin har utökade rättigheter.
- **All data lagras i users.json och guestbook.json.**

---

## Implementation och svårigheter

Jag har valt att använda JSON-filer för att lagra användare och inlägg, vilket var nytt för mig men gav bra förståelse för filhantering i PHP.  
Sessionshantering och cookies har varit utmanande, särskilt att få "Håll mig inloggad" att fungera smidigt och säkert.  
Att hantera rättigheter och se till att endast rätt användare kan redigera eller ta bort inlägg krävde noggrann kontroll i koden.

Jag har varit noga med att använda `password_hash` och `password_verify` för säker lösenordshantering.  
Varje fil har ett tydligt ansvar, vilket gör koden lätt att följa och vidareutveckla.

---

## Lärdomar och resurser

Det svåraste var att felsöka buggar kring session/cookie och att se till att alla flöden fungerar, t.ex. vad som händer om man försöker gå till en skyddad sida utan att vara inloggad.  
Jag har lärt mig mycket om säkerhet i PHP, särskilt kring lösenordshantering och skydd av sidor.

Resurser jag använt:
- PHP-manualen (php.net)
- W3Schools
- YouTube (Codecourse, Traversy Media)
- Stack Overflow

---

## Sammanfattning

Jag är nöjd med resultatet och tycker att projektet gett mig en stabil grund på PHP.  
Det har varit roligt att se hur alla delar hänger ihop och att kunna bygga en komplett lösning från grunden.  
Jag känner mig nu tryggare med att arbeta med både sessions, cookies och filhantering i PHP.