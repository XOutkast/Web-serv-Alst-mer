<?php

include 'header.php';
require_once 'db_cnnt.php';
global $pdo;

// session messages
if (isset($_SESSION['loan_success'])) {
    $success_message = $_SESSION['loan_success'];
    unset($_SESSION['loan_success']);
}

if (isset($_SESSION['loan_error'])) {
    $error_message = $_SESSION['loan_error'];
    unset($_SESSION['loan_error']);
}

$genre_filter = (int)($_GET['genre'] ?? 0);
$language_filter = (int)($_GET['language'] ?? 0);
$sort = $_GET['sort'] ?? 'titel';

$allowed_sorts = ['titel', 'författare', 'genre', 'språk'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'titel';
}

// build query with filters
$sql = "SELECT b.bok_id, b.titel, b.beskrivning, f.namn AS författare, f.författare_id, g.namn AS genre, s.namn AS språk, b.isbn, b.cover_url, g.genre_id, s.språk_id,
    (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id) AS total_exemplar,
    (SELECT COUNT(*) FROM exemplar WHERE bok_id = b.bok_id AND status = 'available') AS available_exemplar
    FROM bok b
    LEFT JOIN författare f ON b.författare_id = f.författare_id
    LEFT JOIN genre g ON b.genre_id = g.genre_id
    LEFT JOIN språk s ON b.språk_id = s.språk_id WHERE 1=1";
$params = [];


// genre filter
if ($genre_filter > 0) {
    $sql .= " AND b.genre_id = ?";
    $params[] = $genre_filter;
}
if ($language_filter > 0) {
    $sql .= " AND b.språk_id = ?";
    $params[] = $language_filter;
}

$sql .= " ORDER BY " . $sort;

$statement = $pdo->prepare($sql);
$statement->execute($params);
$books = $statement->fetchAll();

// get genres & languages for dropdowns
$genres = $pdo->query("SELECT genre_id, namn FROM genre ORDER BY namn")->fetchAll();
$languages = $pdo->query("SELECT språk_id, namn FROM språk ORDER BY namn")->fetchAll();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliotek - Böcker</title>
    <link rel="stylesheet" href="style.css">
    <link
            rel="shortcut icon"
            href="../../Landing_page/bilder/Favicon-A.jpg"
            type="image/x-icon"
    />

</head>
<body>
<div class="container">
    <h1>Böcker</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <!-- Search bar -->
    <div class="search-bar">
        <form method="get" action="search.php">
            <input type="text" name="q" placeholder="Sök böcker, författare, ISBN..." required>
            <button type="submit">Sök</button>
        </form>
    </div>
    
    <!-- Filter Section (Sticky) -->
    <form method="get" class="filter-section sticky-filters" id="filterBar">
        <select name="genre" onchange="this.form.submit()">
            <option value="0">Alla genrer</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= $g['genre_id'] ?>" <?= $genre_filter == $g['genre_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['namn']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="language" onchange="this.form.submit()">
            <option value="0">Alla språk</option>
            <?php foreach ($languages as $l): ?>
                <option value="<?= $l['språk_id'] ?>" <?= $language_filter == $l['språk_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['namn']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="sort" onchange="this.form.submit()">
            <option value="titel" <?= $sort == 'titel' ? 'selected' : '' ?>>Sortera efter titel</option>
            <option value="författare" <?= $sort == 'författare' ? 'selected' : '' ?>>Sortera efter författare</option>
            <option value="genre" <?= $sort == 'genre' ? 'selected' : '' ?>>Sortera efter genre</option>
            <option value="språk" <?= $sort == 'språk' ? 'selected' : '' ?>>Sortera efter språk</option>
        </select>
        <button type="submit">Filtrera</button>
        <?php if ($genre_filter > 0 || $language_filter > 0): ?>
            <a href="home.php" class="btn-clear-filters">Rensa filter</a>
        <?php endif; ?>
    </form>
    
    <!-- Visa böckerna i ett rutnät -->
    <div class="books-grid">
        <?php foreach ($books as $book): ?>
            <div class="book-card">
                <div class="book-card-cover">
                    <?php if (!empty($book['cover_url'])): ?>
                        <img src="<?= htmlspecialchars($book['cover_url']) ?>" 
                             alt="<?= htmlspecialchars($book['titel']) ?>" 
                             loading="lazy">
                        <div class="book-card-overlay">
                            <h4><?= htmlspecialchars($book['titel']) ?></h4>
                            <p class="overlay-author">av <?= htmlspecialchars($book['författare']) ?></p>
                            <?php if (!empty($book['beskrivning'])): ?>
                                <p class="overlay-desc">
                                    <?= htmlspecialchars(mb_substr($book['beskrivning'], 0, 100)) ?>...
                                </p>
                            <?php endif; ?>
                            <div class="overlay-details">
                                <span class="badge"><?= htmlspecialchars($book['genre']) ?></span>
                                <span class="badge"><?= htmlspecialchars($book['språk']) ?></span>
                            </div>
                            <a href="book.php?id=<?= $book['bok_id'] ?>" class="btn-view">Visa detaljer</a>
                        </div>
                    <?php else: ?>
                        <div class="no-cover">
                            <span>📚</span>
                            <p>Inget omslag</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="book-card-info">
                    <h3><a href="book.php?id=<?= $book['bok_id'] ?>"><?= htmlspecialchars($book['titel']) ?></a></h3>
                    <p class="book-author">
                        <a href="authors.php?id=<?= $book['författare_id'] ?>">
                            <?= htmlspecialchars($book['författare']) ?>
                        </a>
                    </p>
                    <div class="book-meta">
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                            <?= htmlspecialchars($book['genre']) ?>
                        </span>
                        <span class="meta-item isbn-meta">ISBN: <?= htmlspecialchars($book['isbn']) ?></span>
                    </div>
                    <div class="book-availability">
                        <?php if ($book['available_exemplar'] > 0): ?>
                            <span class="status-badge available">Tillgänglig (<?= $book['available_exemplar'] ?>)</span>
                        <?php else: ?>
                            <span class="status-badge unavailable"> Utlånad</span>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($_SESSION['user_id']) && $book['available_exemplar'] > 0): ?>
                        <form method="post" action="loan_handler.php" class="card-loan-form">
                            <input type="hidden" name="book_id" value="<?= $book['bok_id'] ?>">
                            <button type="submit" class="btn-loan-card">Låna nu</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Sticky filter bar
window.addEventListener('scroll', function() {
    const filterBar = document.getElementById('filterBar');
    const scrollPosition = window.scrollY;
    
    if (scrollPosition > 200) {
        filterBar.classList.add('stuck');
    } else {
        filterBar.classList.remove('stuck');
    }
});
</script>
</body>
</html>
