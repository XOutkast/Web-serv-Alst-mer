<?php

include 'header.php';
require_once 'db_cnnt.php';
global $pdo;

// Visa meddelanden från session
if (isset($_SESSION['loan_success'])) {
    $success_message = $_SESSION['loan_success'];
    unset($_SESSION['loan_success']);
}
if (isset($_SESSION['loan_error'])) {
    $error_message = $_SESSION['loan_error'];
    unset($_SESSION['loan_error']);
}

// Hämta sökparametrar från URL
$query = trim($_GET['q'] ?? ''); //sök sträng
$search_type = $_GET['type'] ?? 'all'; // allt, titel, författare, isbn

$results = [];
$search_performed = false;

// Gör sökning om query finns
if (!empty($query)) {
    $search_performed = true;
    
    // Skapa sökpaeameter öfr LIKE (wildcards)
    $search_param = '%' . $query . '%';
    
    // anpassa SQL-frågan baserat på söktypen
    if ($search_type === 'title') {
        // Sök endast i boktitel
        $sql = "SELECT b.bok_id, b.titel, f.namn AS författare, f.författare_id, g.namn AS genre, 
                s.namn AS språk, b.isbn, b.beskrivning, b.cover_url,
                (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id AND status = 'available') AS available_exemplar
                FROM bok b
                LEFT JOIN författare f ON b.författare_id = f.författare_id
                LEFT JOIN genre g ON b.genre_id = g.genre_id
                LEFT JOIN språk s ON b.språk_id = s.språk_id
                WHERE b.titel LIKE ?
                ORDER BY b.titel";
        $statement = $pdo->prepare($sql);
        $statement->execute([$search_param]);
    } elseif ($search_type === 'author') {
        // Sök endast i författarnamn
        $sql = "SELECT b.bok_id, b.titel, f.namn AS författare, f.författare_id, g.namn AS genre, 
                s.namn AS språk, b.isbn, b.beskrivning, b.cover_url,
                (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id AND status = 'available') AS available_exemplar
                FROM bok b
                LEFT JOIN författare f ON b.författare_id = f.författare_id
                LEFT JOIN genre g ON b.genre_id = g.genre_id
                LEFT JOIN språk s ON b.språk_id = s.språk_id
                WHERE f.namn LIKE ?
                ORDER BY f.namn, b.titel";
        $statement = $pdo->prepare($sql);
        $statement->execute([$search_param]);
    } elseif ($search_type === 'isbn') {
        // Sök endast i ISBN-nummer
        $sql = "SELECT b.bok_id, b.titel, f.namn AS författare, f.författare_id, g.namn AS genre, 
                s.namn AS språk, b.isbn, b.beskrivning, b.cover_url,
                (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id AND status = 'available') AS available_exemplar
                FROM bok b
                LEFT JOIN författare f ON b.författare_id = f.författare_id
                LEFT JOIN genre g ON b.genre_id = g.genre_id
                LEFT JOIN språk s ON b.språk_id = s.språk_id
                WHERE b.isbn LIKE ?
                ORDER BY b.titel";
        $statement = $pdo->prepare($sql);
        $statement->execute([$search_param]);
    } else { 
        // 'allt' - Sök i titel, författare och ISBN samtidigt
        $sql = "SELECT b.bok_id, b.titel, f.namn AS författare, f.författare_id, g.namn AS genre, 
                s.namn AS språk, b.isbn, b.beskrivning, b.cover_url,
                (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id AND status = 'available') AS available_exemplar
                FROM bok b
                LEFT JOIN författare f ON b.författare_id = f.författare_id
                LEFT JOIN genre g ON b.genre_id = g.genre_id
                LEFT JOIN språk s ON b.språk_id = s.språk_id
                WHERE b.titel LIKE ? OR f.namn LIKE ? OR b.isbn LIKE ?
                ORDER BY 
                    CASE 
                        WHEN b.titel LIKE ? THEN 1
                        WHEN f.namn LIKE ? THEN 2
                        WHEN b.isbn LIKE ? THEN 3
                        ELSE 4
                    END,
                    b.titel";

        $statement = $pdo->prepare($sql);
        // Skicka samma sökparameter flera gånger för LIKE i alla kolumner
        $statement->execute([$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    // hämta alla matchande rader från db som en array
    $results = $statement->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sök böcker - Bibliotek</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Sök böcker</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <form method="get" class="search-form">
        <input type="text" 
               name="q" 
               placeholder="Sök efter titel, författare, ISBN..." 
               value="<?= htmlspecialchars($query) ?>"
               required
               autofocus>
        
        <select name="type">
            <option value="all" <?= $search_type === 'all' ? 'selected' : '' ?>>Sök i allt</option>
            <option value="title" <?= $search_type === 'title' ? 'selected' : '' ?>>Endast titel</option>
            <option value="author" <?= $search_type === 'author' ? 'selected' : '' ?>>Endast författare</option>
            <option value="isbn" <?= $search_type === 'isbn' ? 'selected' : '' ?>>Endast ISBN</option>
        </select>
        
        <button type="submit">Sök</button>
    </form>
    
    <?php if ($search_performed): ?>
        <div class="search-results">
            <h2>Sökresultat för "<?= htmlspecialchars($query) ?>"</h2>
            <p><?= count($results) ?> bok<?= count($results) !== 1 ? 'er' : '' ?> hittades</p>
            
            <?php if (count($results) > 0): ?>
                <!--  Visas resutatet i en tabell med omslag, titel, författare, genre, språk, ISBN, beskrivning och tillgänglighet -->
                <table>
                    <thead>
                        <tr>
                            <th>Omslag</th>
                            <th>Titel</th>
                            <th>Författare</th>
                            <th>Genre</th>
                            <th>Språk</th>
                            <th>ISBN</th>
                            <th>Beskrivning</th>
                            <th>Tillgänglighet</th>
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <th>Åtgärd</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $book): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($book['cover_url'])): ?>
                                        <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="Omslag" class="book-cover">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Länk till bokens detaljsida -->
                                    <a href="book.php?id=<?= $book['bok_id'] ?>">
                                        <?= htmlspecialchars($book['titel']) ?>
                                    </a>
                                </td>
                                <td>
                                    <!-- Länk till författarens sida -->
                                    <a href="authors.php?id=<?= $book['författare_id'] ?>">
                                        <?= htmlspecialchars($book['författare']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($book['genre']) ?></td>
                                <td><?= htmlspecialchars($book['språk']) ?></td>
                                <td><?= htmlspecialchars($book['isbn']) ?></td>
                                <td class="book-description">
                                    <?php 
                                    // Visa de första 100 tecknen av en bok beskrivningen
                                    $desc = htmlspecialchars($book['beskrivning']);
                                    echo mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc;
                                    ?>
                                </td>
                                <td>
                                    <?php if ($book['available_exemplar'] > 0): ?>
                                        <span class="available">Tillgänglig (<?= $book['available_exemplar'] ?>)</span>
                                    <?php else: ?>
                                        <span class="unavailable">Utlånad</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <td>
                                    <?php if ($book['available_exemplar'] > 0): ?>
                                        <!-- Låneknapp visas endast om boken är tillgänglig -->
                                        <form method="post" action="loan_handler.php" style="display: inline;">
                                            <input type="hidden" name="book_id" value="<?= $book['bok_id'] ?>">
                                            <button type="submit" class="btn-borrow">Låna</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Inget resultat hittades -->
                <div class="no-results">
                    <p>Inga böcker matchade din sökning.</p>
                    <p>Försök Igen:</p>
                   
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Söktips -->
        <div class="search-tips">
            <h3>Söktips</h3>
            <ul>
                <li>Sök efter boktitel (t.ex. "Lorem Ipsum", "1984")</li>
                <li>Sök efter författare (t.ex. "Astrid Lindgren", " Orwell")</li>
                <li>Sök efter ISBN (t.ex. "123-45-67-8912-9")</li>
                <li>Använd filter om du vill söka specifikt i titel, författare eller ISBN</li>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="back-link">
        <a href="home.php">Tillbaka till alla böcker</a>
    </div>
</div>
</body>
</html>
