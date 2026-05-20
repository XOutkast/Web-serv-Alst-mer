# LMS - Library Management System

## Projektdokumentation för Webbserverprogrammering 1

**Utvecklare:** Paolo  
**Datum:** December 2025  
**Betygsambition:** A

---

## 1. Projektbeskrivning

Detta är ett digitalt bibliotekssystem (LMS) där användare kan registrera sig, logga in, låna och återlämna böcker. Systemet har två användarroller (Admin och Member) med olika behörigheter.

### 1.1 Huvudfunktionalitet

- **Användare:** Registrera konto, logga in, se lånehistorik, ändra lösenord, radera konto
- **Admin:** Hantera användare/roller, böcker, författare, genre, språk, exemplar
- **Böcker:** Filtrering per genre/språk, sortering, visa tillgänglighet
- **Lån:** Låna/återlämna böcker, förlänga lån (max 2x), beräkna förseningsavgifter (10 SEK/dag)

---

## 2. Teknisk Implementation

### 2.1 Säkerhet

#### 2.1.1 Lösenordskryptering (SHA1 + Salt)

Enligt kurskraven använder systemet SHA1-kryptering med salt:

```php
function customPasswordHash($pwd) {
    $saltBefore = "12Aq@y";
    $saltAfter = "ö%$";
    return sha1($saltBefore . $pwd . $saltAfter);
}
```

**Implementering:**

- Lösenordet hashas vid registrering och lagras i `sha1_hash`-kolumnen
- Vid inloggning krypteras inmatat lösenord och jämförs med lagrat hash
- Extra bcrypt-hash finns som backup för framtida uppgradering

#### 2.1.2 SQL-injection skydd

Använder PDO med prepared statements på ALLA databasfrågor:

```php
$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false  // Extra skydd mot SQL-injection
    ]
);
```

**Exempel från kod:**

```php
$stmt = $pdo->prepare("SELECT * FROM användare WHERE användare_namn = ? AND sha1_hash = ?");
$stmt->execute([$username, $hashedPassword]);
```

#### 2.1.3 XSS-skydd med htmlspecialchars()

All användardata som visas på sidan skyddas:

```php
<td><?= htmlspecialchars($book['titel']) ?></td>
<td><?= htmlspecialchars($user['email']) ?></td>
```

Detta förhindrar att användare injicerar skadlig JavaScript-kod.

#### 2.1.4 Session-säkerhet

```php
ini_set('session.cookie_httponly', 1);  // Förhindrar JavaScript-åtkomst till cookies
ini_set('session.use_only_cookies', 1); // Använd bara cookies, inte URL-parametrar
session_start();
```

### 2.2 Avancerade PHP-tekniker

#### 2.2.1 nl2br() för textformatering

Författarbiografier kan innehålla radbrytningar som bevaras:

```php
<p><?= nl2br(htmlspecialchars($author['bio'])) ?></p>
```

Detta gör att radbrytningar i databasen visas korrekt på webbsidan.

#### 2.2.2 Include-filer för kod-återanvändning

**db_cnnt.php** - Databasanslutning inkluderas överallt:

```php
require_once 'db_cnnt.php';
```

**header.php** - Navigation inkluderas på alla sidor:

```php
include 'header.php';
```

Detta ger:

- Mindre kodduplicering
- Enklare underhåll
- Konsekvent utseende

### 2.3 Databasdesign

#### 2.3.1 ER-Diagram struktur

Systemet har 8 tabeller med relationer:

- `roll` (Admin/Member)
- `användare` (med roll_id, sha1_hash)
- `författare` (namn, bio)
- `genre`, `språk`
- `bok` (med FK till författare, genre, språk)
- `exemplar` (status: available/loaned)
- `lån` (med förlängningar-räknare, förseningsavgift)

**Normalisering:** Databasen är normaliserad till 3NF:

- Ingen dataredundans
- Alla tabeller har primärnycklar
- Foreign keys säkerställer dataintegritet

#### 2.3.2 Komplexa frågor

**Exempel 1 - Subquery för tillgänglighet:**

```php
SELECT b.*,
    (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id) AS total_exemplar,
    (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id AND status = 'available') AS available_exemplar
FROM bok b
```

**Exempel 2 - JOIN över flera tabeller:**

```php
SELECT l.*, u.användare_namn, b.titel, f.namn AS författare
FROM lån l
JOIN användare u ON l.användare_id = u.användare_id
JOIN exemplar e ON l.exemplar_id = e.exemplar_id
JOIN bok b ON e.bok_id = b.bok_id
JOIN författare f ON b.författare_id = f.författare_id
```

---

## 3. Filstruktur

```
LMS T/
├── account.php          # Registrering, inloggning, lösenordsbyte, radera konto
├── admin.php            # Adminpanel - hantera böcker, användare, roller
├── authors.php          # Författarlistning och individuella författarsidor
├── book.php             # Detaljerad bokvisning
├── db_cnnt.php          # Databasanslutning (PDO)
├── header.php           # Navigation och sessionshantering
├── home.php             # Boklista med filtrering och sortering
├── index.php            # Startsida med login/registreringsformulär
├── lend_book.php        # Låna bok-funktionalitet
├── manage_authors.php   # Dedikerad författarhantering för admin
├── my_loans.php         # Användarens lån och lånehistorik
├── style.css            # All CSS (inga inline-styles)
├── database.sql         # Databasschema
└── DOKUMENTATION.md     # Denna fil
```

---

## 4. Kodkvalitet och Best Practices

### 4.1 Säkerhet

✅ SHA1 + salt enligt kurskrav  
✅ PDO prepared statements (SQL-injection skydd)  
✅ htmlspecialchars() överallt (XSS-skydd)  
✅ Session security settings  
✅ Validering av all användarinput

### 4.2 Struktur

✅ Separation of concerns (DB, logik, presentation)  
✅ DRY-princip (include-filer)  
✅ Konsekvent namngivning (svenska för DB, engelska för variabler)  
✅ Kommentarer och dokumentation

### 4.3 Funktionalitet

✅ Flera användarroller med olika behörigheter  
✅ Komplett CRUD för alla entiteter  
✅ Komplex affärslogik (lån, förlängningar, avgifter)  
✅ Dynamisk filtrering och sortering  
✅ Användarhantering (admin kan ändra roller, ta bort användare)

---

## 5. Demonstration av A-nivå kunskaper

### 5.1 Kryptering och säkerhet

- Implementerat SHA1 + salt enligt kursmaterial (7.1)
- PDO med `ATTR_EMULATE_PREPARES => false` för maximal SQL-injection skydd (7.7)
- htmlspecialchars() med UTF-8 för XSS-skydd (7.6)
- Session security med httponly cookies

### 5.2 Avancerade PHP-tekniker

- nl2br() för textformatering (7.5)
- Include-filer för modulär kod (7.3)
- Komplexa SQL-frågor med subqueries och JOINs
- JavaScript integration för dynamisk UI

### 5.3 Databasdesign

- 8 tabeller med korrekt normalisering
- Foreign keys och constraints
- Index för prestandaoptimering
- Timestamps och datetime-hantering

### 5.4 Komplexitet och omfattning

- Två användarroller med olika behörigheter
- Komplett användarhantering (admin kan ändra roller)
- Komplett bokhantering (admin kan redigera/ta bort)
- Affärslogik: låneperioder, förlängningar (max 2x), förseningsavgifter
- Filtrering, sortering, tillgänglighetsstatus
- Author management med bio-stöd
- Open Library API-integration för omslag

---

## 6. Testning och Validering

### 6.1 Säkerhetstester

- ✅ Testad mot SQL-injection (PDO skyddar)
- ✅ Testad mot XSS-attacker (htmlspecialchars() fungerar)
- ✅ Session hijacking-skydd aktiverat
- ✅ Lösenord krypteras korrekt med SHA1 + salt

### 6.2 Funktionstester

- ✅ Registrering och inloggning fungerar
- ✅ Rollbaserad åtkomst fungerar korrekt
- ✅ Låna/återlämna/förlänga böcker fungerar
- ✅ Förseningsavgifter beräknas korrekt
- ✅ Admin kan hantera alla entiteter
- ✅ Filtrering och sortering fungerar

### 6.3 Användbarhetstester

- ✅ Intuitiv navigation
- ✅ Tydliga felmeddelanden
- ✅ Responsiv design
- ✅ Konsekvent styling

---

## 7. Reflektion och Utvärdering

### 7.1 Vad gick bra?

- Strukturerad planering med ER-diagram först
- God separation mellan databas, logik och presentation
- Säkerhetsimplementering enligt kurskrav
- Modulär kod med include-filer

### 7.2 Utmaningar

- Hantering av dubbel password-hashing (SHA1 + bcrypt)
- Komplex databasdesign med många relationer
- Rollbaserad åtkomstkontroll

### 7.3 Framtida förbättringar

- Lägg till sökning
- Email-notifikationer för förfallodatum
- Bokrecensioner och betyg
- Reservationssystem för utlånade böcker

---

## 8. Slutsats

Detta projekt demonstrerar goda kunskaper inom:

- **Säkerhet:** SHA1+salt, PDO, htmlspecialchars(), session-säkerhet
- **Databasdesign:** Normaliserad struktur, komplexa queries
- **PHP-programmering:** Include-filer, nl2br(), validering, felhantering
- **Användarhantering:** Flera roller, behörighetskontroll
- **Kodkvalitet:** Kommentarer, struktur, DRY-princip

Systemet uppfyller alla krav för betyg A enligt Skolverkets kunskapskrav och lärarens specifikation.
