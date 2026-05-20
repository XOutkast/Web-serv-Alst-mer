<?php

include 'header.php';
require_once 'db_cnnt.php';

// Kolla så det bara är admins som får vara här
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: index.php?noaccess=1');
    exit;
}

global $pdo;
$message = '';
$edit_author = null;

// Om admin uppdaterar en författare (namn eller bio)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_author'])) {
    $author_id = (int)$_POST['author_id'];
    $namn = trim($_POST['namn'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    if ($author_id && $namn) {
        $pdo->prepare("UPDATE författare SET namn = ?, bio = ? WHERE författare_id = ?")->execute([$namn, $bio, $author_id]);
        $message = 'Författare uppdaterad!';
    }
}

// Om admin vill radera en författare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_author'])) {
    $author_id = (int)$_POST['author_id'];
    if ($author_id) {
        // kontrollera först om författaren har böcker (kan inte radera den då)
        $statement = $pdo->prepare("SELECT COUNT(*) as count FROM bok WHERE författare_id = ?");
        $statement->execute([$author_id]);
        $result = $statement->fetch();
        if ($result['count'] > 0) {
            $message = 'Kan inte radera författare som har böcker!';
        } else {
            // går att radera,om inga böcker finns från denna författare
            $pdo->prepare("DELETE FROM författare WHERE författare_id = ?")->execute([$author_id]);
            $message = 'Författare raderad!';
        }
    }
}

// Redigera en författare (från URL-parameter)
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $statement = $pdo->prepare("SELECT * FROM författare WHERE författare_id = ?");
    $statement->execute([$edit_id]);
    $edit_author = $statement->fetch();
}

// Hämta alla författare med hur många böcker de har
$statement = $pdo->query("SELECT f.författare_id, f.namn, f.bio, COUNT(b.bok_id) as antal_böcker 
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
    <title>Hantera författare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Hantera författare</h1>
    <?php if ($message): ?>
        <div class="notice success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($edit_author): ?>
        <div class="edit-section">
            <h2>Redigera: <?= htmlspecialchars($edit_author['namn']) ?></h2>
            <form method="post">
                <input type="hidden" name="author_id" value="<?= $edit_author['författare_id'] ?>">
                <input type="text" name="namn" value="<?= htmlspecialchars($edit_author['namn']) ?>" placeholder="Författarens namn" required>
                <textarea name="bio" rows="5" placeholder="Biografi..."><?= htmlspecialchars($edit_author['bio'] ?? '') ?></textarea>
                <button type="submit" name="update_author">Spara ändringar</button>
                <a href="manage_authors.php" class="btn-cancel">Avbryt</a>
            </form>
        </div>
    <?php endif; ?>
    
    <h2>Alla författare</h2>
    <table>
        <thead>
            <tr>
                <th>Namn</th>
                <th>Antal böcker</th>
                <th>Biografi</th>
                <th>Åtgärder</th>
            </tr>
        </thead>
        <tbody>
        // tabell-rad för varje författare
            <?php foreach ($authors as $author): ?>
                <tr>
                    <td><?= htmlspecialchars($author['namn']) ?></td>
                    <td><?= $author['antal_böcker'] ?></td>
                    <td><?= !empty($author['bio']) ? substr(htmlspecialchars($author['bio']), 0, 60) . '...' : '<em>Ingen biografi</em>' ?></td>
                    <td>
                        <a href="manage_authors.php?edit=<?= $author['författare_id'] ?>" class="btn-small">Redigera</a>
                        <?php if ($author['antal_böcker'] == 0): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="author_id" value="<?= $author['författare_id'] ?>">
                                <button type="submit" name="delete_author" class="btn-small btn-danger" onclick="return confirm('Radera denna författare?')">Radera</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <a href="admin.php" class="btn-back">← Tillbaka till admin</a>
</div>
</body>
</html>
