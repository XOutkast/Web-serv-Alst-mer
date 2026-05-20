<?php

include 'header.php';
require_once 'db_cnnt.php';
global $pdo;

// Kolla om vi ska visa en specifik författare
$author_id = (int)($_GET['id'] ?? 0);

if ($author_id > 0) {
    // Visa detaljer om en viss författare
    $statement = $pdo->prepare("SELECT * FROM författare WHERE författare_id = ?");
    $statement->execute([$author_id]);
    $author = $statement->fetch();
    
    // Om författaren inte finns, tillbaka till listan
    if (!$author) {
        header('Location: authors.php');
        exit;
    }
    
    // Hämta alla böcker av denna författare
    $statement = $pdo->prepare("SELECT b.bok_id, b.titel, g.namn AS genre, s.namn AS språk, b.isbn, b.cover_url 
        FROM bok b 
        LEFT JOIN genre g ON b.genre_id = g.genre_id 
        LEFT JOIN språk s ON b.språk_id = s.språk_id 
        WHERE b.författare_id = ? 
        ORDER BY b.titel");
    $statement->execute([$author_id]);
    $books = $statement->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="sv">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($author['namn']) ?> - Författare</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="container">
        <h1><?= htmlspecialchars($author['namn']) ?></h1>
        <?php if (!empty($author['bio'])): ?>
            <div class="author-bio">
                <h3>Biografi</h3>
                <p><?= nl2br(htmlspecialchars($author['bio'])) ?></p>
            </div>
        <?php endif; ?>
        
        <h2>Böcker av <?= htmlspecialchars($author['namn']) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Omslag</th>
                    <th>Titel</th>
                    <th>Genre</th>
                    <th>Språk</th>
                    <th>ISBN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td>
                            <?php if (!empty($book['cover_url'])): ?>
                                <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="Omslag" class="book-cover">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($book['titel']) ?></td>
                        <td><?= htmlspecialchars($book['genre']) ?></td>
                        <td><?= htmlspecialchars($book['språk']) ?></td>
                        <td><?= htmlspecialchars($book['isbn']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="authors.php" class="btn-back">Tillbaka till alla författare</a>
    </div>
    </body>
    </html>
    <?php
} else {
    // OM ingen specifik författare vald - visa alla
    $statement = $pdo->query("SELECT f.författare_id, f.namn, COUNT(b.bok_id) as antal_böcker 
        FROM författare f 
        LEFT JOIN bok b ON f.författare_id = b.författare_id 
        GROUP BY f.författare_id 
        ORDER BY f.namn");
    $authors = $statement->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="sv">
    <head>
        <meta charset="UTF-8">
        <title>Författare</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="container">
        <h1>Författare</h1>
        <table>
            <thead>
                <tr>
                    <th>Författare</th>
                    <th>Antal böcker</th>
                    <th>Åtgärd</th>
                </tr>
            </thead>
            <tbody>
                <!--loop for authors as author-->
                <?php foreach ($authors as $author): ?>
                    <tr>
                        <td><?= htmlspecialchars($author['namn']) ?></td>
                        <td><?= $author['antal_böcker'] ?></td>
                        <td><a href="authors.php?id=<?= $author['författare_id'] ?>">Visa böcker →</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </body>
    </html>
    <?php
}
?>
