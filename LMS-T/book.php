<?php
include 'header.php';
require_once 'db_cnnt.php';
global $pdo;

// Hämta bok-id från URL:en; om inte - använd "0"
$book_id = (int)($_GET['id'] ?? 0);

// Visa notiser (som "Boken är lånad")
if (isset($_SESSION['loan_success'])) {
    $success_message = $_SESSION['loan_success'];
    unset($_SESSION['loan_success']);
}
// kolla om loan_error finns ......
if (isset($_SESSION['loan_error'])) {
    $error_message = $_SESSION['loan_error'];
    unset($_SESSION['loan_error']);
}

// Om inte giltig bok-id, skicka tillbaka till home.php
if ($book_id <= 0) {
    header('Location: home.php');
    exit;
}

// Hämta all info om boken (titel, författare, genre, språk osv.)
$statement = $pdo->prepare("SELECT b.*, f.namn AS författare, f.författare_id, f.bio AS författare_bio, g.namn AS genre, g.genre_id, s.namn AS språk, s.språk_id 
    FROM bok b 
    LEFT JOIN författare f ON b.författare_id = f.författare_id 
    LEFT JOIN genre g ON b.genre_id = g.genre_id 
    LEFT JOIN språk s ON b.språk_id = s.språk_id 
    WHERE b.bok_id = ?");
$statement->execute([$book_id]);
$book = $statement->fetch();

// Om boken inte finns, gå tillbaka till startsidan
if (!$book) {
    header('Location: home.php');
    exit;
}

// Kolla hur många exemplar som finns tillgängliga att låna
$statement = $pdo->prepare("SELECT COUNT(*) as available FROM exemplar WHERE bok_id = ? AND status = 'available'");
$statement->execute([$book_id]);
$availability = $statement->fetch();

// Hitta andra böcker av samma författare (max 5 st)
$statement = $pdo->prepare("SELECT bok_id, titel, cover_url FROM bok WHERE författare_id = ? AND bok_id != ? LIMIT 5");
$statement->execute([$book['författare_id'], $book_id]);
$other_books = $statement->fetchAll();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($book['titel']) ?> - Bok</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <?php if (isset($success_message)): ?>
        <div class="notice success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <div class="book-detail">
        <?php if (!empty($book['cover_url'])): ?>
            <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="Omslag" class="book-cover-large">
        <?php endif; ?>
        
        <div class="book-info">
            <h1><?= htmlspecialchars($book['titel']) ?></h1>
            
            <div class="info-row">
                <span class="info-label">Författare:</span> 
                <a href="authors.php?id=<?= $book['författare_id'] ?>">
                    <?= htmlspecialchars($book['författare']) ?>
                </a>
            </div>
            
            <div class="info-row">
                <span class="info-label">Genre:</span> <a href="home.php?genre=<?= $book['genre_id'] ?>"><?= htmlspecialchars($book['genre']) ?></a>
            </div>
            
            <div class="info-row">
                <span class="info-label">Språk:</span> <a href="home.php?language=<?= $book['språk_id'] ?>"><?= htmlspecialchars($book['språk']) ?></a>
            </div>
            
            <div class="info-row">
                <span class="info-label">ISBN:</span> <?= htmlspecialchars($book['isbn']) ?>
            </div>
            
            <?php if (!empty($book['beskrivning'])): ?>
                <div class="book-description">
                    <h3>Beskrivning</h3>
                    <p><?= nl2br(htmlspecialchars($book['beskrivning'])) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($availability['available'] > 0): ?>
                <!-- Boken finns! - Visa hur många exemplar -->
                <div class="availability">
                     Tillgänglig (<?= $availability['available'] ?> exemplar)
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Visa låneknapp om användaren är inloggad -->
                    <form method="post" action="loan_handler.php" style="margin-top: 15px;">
                        <input type="hidden" name="book_id" value="<?= $book_id ?>">
                        <button type="submit" class="btn-primary">Låna denna bok</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <!-- Boken är slut, ingen tillgänglig just nu -->
                <div class="availability unavailable">
                     Ej tillgänglig
                </div>
            <?php endif; ?>
            
            <?php if (!empty($book['författare_bio'])): ?>
                <div class="author-bio">
                    <h3>Om författaren</h3>
                    <p><?= nl2br(htmlspecialchars($book['författare_bio'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (count($other_books) > 0): ?>
        <div class="other-books">
            <h2>Fler böcker av <?= htmlspecialchars($book['författare']) ?></h2>
            <div class="book-grid">
                <?php foreach ($other_books as $other): ?>
                    <div class="book-card">
                        <a href="book.php?id=<?= $other['bok_id'] ?>">
                            <?php if (!empty($other['cover_url'])): ?>
                                <img src="<?= htmlspecialchars($other['cover_url']) ?>" alt="<?= htmlspecialchars($other['titel']) ?>">
                            <?php endif; ?>
                            <div><?= htmlspecialchars($other['titel']) ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <a href="home.php" class="btn-back">Tillbaka till alla böcker</a>
</div>
</body>
</html>
