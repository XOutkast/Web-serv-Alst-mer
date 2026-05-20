<?php

include 'header.php';
require_once 'db_cnnt.php';

// kaliya admin-ka halkan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: index.php?noaccess=1');
    exit;
}

global $pdo;
// Fariimaha kala duwan
$addBookMsg = '';
$userMsg = '';
$exemplarMsg = '';
$genreMsg = '';
$languageMsg = '';

// add genre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_genre'])) {
    $genre_namn = trim($_POST['genre_namn'] ?? '');
    if ($genre_namn) {
        // Kolla så genren inte redan finns
        $statement = $pdo->prepare("SELECT genre_id FROM genre WHERE namn = ?");
        $statement->execute([$genre_namn]);
        if ($statement->fetch()) {
            $genreMsg = 'Genre finns redan!';
        } else {
            $pdo->prepare("INSERT INTO genre (namn) VALUES (?)")->execute([$genre_namn]);
            $genreMsg = 'Genre tillagd!';
        }
    }
}
// POST-förfrågan - lägg till ett nytt språk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_language'])) {
    $sprak_namn = trim($_POST['sprak_namn'] ?? '');
    if ($sprak_namn) {
        // Kolla så språket inte redan finns
        $statement = $pdo->prepare("SELECT språk_id FROM språk WHERE namn = ?");
        $statement->execute([$sprak_namn]);
        if ($statement->fetch()) {
            $languageMsg = 'Språk finns redan!';
        } else {
            // lägg till nytt språket
            $pdo->prepare("INSERT INTO språk (namn) VALUES (?)")->execute([$sprak_namn]);
            $languageMsg = 'Språk tillagt!';
        }
    }
}

//  Ändra användarroll (t.ex. från vanlig användare till admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = (int)$_POST['role_id'];
    if ($user_id && $new_role) {
        $pdo->prepare("UPDATE användare SET roll_id = ? WHERE användare_id = ?")->execute([$new_role, $user_id]);
        $userMsg = 'Roll uppdaterad!';
    }
}

// Ta bort en användare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user'])) {
    $user_id = (int)$_POST['user_id'];
    if ($user_id && $user_id != $_SESSION['user_id']) {
        // Kolla om användaren har aktiva lån (då kan vi inte ta bort dem)
        $statement = $pdo->prepare("SELECT COUNT(*) FROM lån WHERE användare_id = ? AND återlämnad_datum IS NULL");
        $statement->execute([$user_id]);
        if ($statement->fetchColumn() > 0) {
            $userMsg = 'Kan inte ta bort användare med aktiva lån!';
        } else {
            // Radera först användarens lånehistorik
            $pdo->prepare("DELETE FROM lån WHERE användare_id = ?")->execute([$user_id]);
            // Sedan användaren själv
            $pdo->prepare("DELETE FROM användare WHERE användare_id = ?")->execute([$user_id]);
            $userMsg = 'Användare borttagen!';
        }
    } elseif ($user_id == $_SESSION['user_id']) {
        $userMsg = 'Du kan inte ta bort dig själv!';
    }
}

// Lägg till ett nytt exemplar av en bok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exemplar'])) {
    $bok_id = (int)$_POST['bok_id'];
    $status = $_POST['status'] ?? 'available';
    if ($bok_id) {
        $pdo->prepare("INSERT INTO exemplar (bok_id, status) VALUES (?, ?)")->execute([$bok_id, $status]);
        $exemplarMsg = 'Exemplar tillagt!';
    }
}

//  Ändra status på ett exemplar (tillgänglig/utlånad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_exemplar'])) {
    $exemplar_id = (int)$_POST['exemplar_id'];
    $status = $_POST['status'];
    if ($exemplar_id && in_array($status, ['available', 'loaned'])) {
        $pdo->prepare("UPDATE exemplar SET status = ? WHERE exemplar_id = ?")->execute([$status, $exemplar_id]);
        $exemplarMsg = 'Status uppdaterad!';
    }
}
// Lägg till ett nytt bok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $titel = trim($_POST['titel'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre_id = (int)($_POST['genre_id'] ?? 0);
    $sprak_id = (int)($_POST['sprak_id'] ?? 0);
    $isbn = trim($_POST['isbn'] ?? '');
    $cover_url = trim($_POST['cover_url'] ?? '');
    if ($titel && $author && $genre_id && $sprak_id && $isbn) {
        // Kolla om författaren finns, annars skapa ny
        $statement = $pdo->prepare("SELECT författare_id FROM författare WHERE namn = ?");
        $statement->execute([$author]);
        $authorRow = $statement->fetch();
        if ($authorRow) {
            $author_id = $authorRow['författare_id'];
        } else {
            // Ny författare, lägg till den
            $statement = $pdo->prepare("INSERT INTO författare (namn) VALUES (?)");
            $statement->execute([$author]);
            $author_id = $pdo->lastInsertId();
        }
        // Om ingen omslags-URL angavs, generera en från ISBN
        if (empty($cover_url) && !empty($isbn)) {
            $cover_url = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
        }
        // Lägg till boken i databasen
        $statement = $pdo->prepare("INSERT INTO bok (titel, författare_id, genre_id, språk_id, isbn, cover_url) VALUES (?, ?, ?, ?, ?, ?)");
        $statement->execute([$titel, $author_id, $genre_id, $sprak_id, $isbn, $cover_url]);
        $addBookMsg = 'Boken har lagts till!';
    } else {
        $addBookMsg = 'Fyll i alla fält.';
    }
}

// Redigera en befintlig bok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_book'])) {
    $bok_id = (int)$_POST['bok_id'];
    $titel = trim($_POST['titel'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre_id = (int)($_POST['genre_id'] ?? 0);
    $sprak_id = (int)($_POST['sprak_id'] ?? 0);
    $isbn = trim($_POST['isbn'] ?? '');
    $cover_url = trim($_POST['cover_url'] ?? '');

    // Kontrollera att alla fält för uppdatering av boken finns
    if ($bok_id && $titel && $author && $genre_id && $sprak_id && $isbn) {
        // Hitta eller skapa författaren
        $statement = $pdo->prepare("SELECT författare_id FROM författare WHERE namn = ?");
        $statement->execute([$author]);
        $authorRow = $statement->fetch();
        if ($authorRow) {
            $author_id = $authorRow['författare_id'];
        } else {
            $statement = $pdo->prepare("INSERT INTO författare (namn) VALUES (?)");
            $statement->execute([$author]);
            $author_id = $pdo->lastInsertId(); // ID för den nya författare
        }
        // Generera omslags-URL om det är tomt
        if (empty($cover_url) && !empty($isbn)) {
            $cover_url = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
        }
        // Uppdatera bokens info
        $statement = $pdo->prepare("UPDATE bok SET titel = ?, författare_id = ?, genre_id = ?, språk_id = ?, isbn = ?, cover_url = ? WHERE bok_id = ?");
        $statement->execute([$titel, $author_id, $genre_id, $sprak_id, $isbn, $cover_url, $bok_id]);
        $addBookMsg = 'Boken har uppdaterats!';
    }
}

// Ta bort en bok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $bok_id = (int)$_POST['bok_id'];
    if ($bok_id) {
        // Kolla om boken har exemplar (om det har då, det inte inte att ta bort)
        $statement = $pdo->prepare("SELECT COUNT(*) FROM exemplar WHERE bok_id = ?");
        $statement->execute([$bok_id]);
        $count = $statement->fetchColumn();
        
        if ($count > 0) {
            $addBookMsg = 'Kan inte ta bort bok med exemplar! Ta bort exemplaren först.';
        } else {
            $pdo->prepare("DELETE FROM bok WHERE bok_id = ?")->execute([$bok_id]);
            $addBookMsg = 'Boken har tagits bort!';
        }
    }
}

// Hämta alla genrer, språk, roller och böcker
$genres = $pdo->query("SELECT genre_id, namn FROM genre")->fetchAll();
$sprak = $pdo->query("SELECT språk_id, namn FROM språk")->fetchAll();
$roles = $pdo->query("SELECT roll_id, roll_namn FROM roll")->fetchAll();
$books_for_exemplar = $pdo->query("SELECT bok_id, titel FROM bok ORDER BY titel")->fetchAll();

// Hämta alla användare med deras roller
$statement = $pdo->prepare("SELECT a.användare_id, a.användare_namn, a.email, r.roll_namn, r.roll_id, a.skapad FROM användare a JOIN roll r ON a.roll_id = r.roll_id");
$statement->execute();
$users = $statement->fetchAll();

// Hämta alla exemplar med boktitlar och författare
$exemplars = $pdo->query("SELECT e.exemplar_id, e.status, b.titel, f.namn AS författare FROM exemplar e JOIN bok b ON e.bok_id = b.bok_id JOIN författare f ON b.författare_id = f.författare_id ORDER BY b.titel")->fetchAll();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adminpanel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Adminpanel</h1>
    
    <!-- Tab Nav -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="showTab('books', this)">Böcker</button>
        <button class="tab-btn" onclick="showTab('users', this)">Användare</button>
        <button class="tab-btn" onclick="showTab('loans', this)">Lån</button>
        <button class="tab-btn" onclick="showTab('settings', this)">Inställningar</button>
    </div>
    
    <!-- Books Tab -->
    <div id="books-tab" class="tab-content active">
    <h2>Lägg till bok</h2>
    <?php if ($addBookMsg) echo '<div class="notice success">' . htmlspecialchars($addBookMsg) . '</div>'; ?>
    <form method="post">
        <input type="text" name="titel" placeholder="Boktitel" required>
        <input type="text" name="author" placeholder="Författare" required>
        <select name="genre_id" required>
            <option value="">Välj genre</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= $g['genre_id'] ?>"><?= htmlspecialchars($g['namn']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sprak_id" required>
            <option value="">Välj språk</option>
            <?php foreach ($sprak as $s): ?>
                <option value="<?= $s['språk_id'] ?>"><?= htmlspecialchars($s['namn']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="isbn" placeholder="ISBN" required>
        <input type="text" name="cover_url" placeholder="Omslags-URL (https://...)">
        <button type="submit" name="add_book">Lägg till bok</button>
    </form>
    
    <h2>Hantera författare</h2>
    <p><a href="manage_authors.php" class="btn-primary">Gå till författarhantering</a></p>
    
    <h2>Hantera böcker</h2>
    <?php
    // hämta alla böcker
    $all_books = $pdo->query("SELECT b.bok_id, b.titel, f.namn AS författare, g.namn AS genre, s.namn AS språk, b.isbn, b.cover_url 
        FROM bok b 
        JOIN författare f ON b.författare_id = f.författare_id 
        JOIN genre g ON b.genre_id = g.genre_id 
        JOIN språk s ON b.språk_id = s.språk_id 
        ORDER BY b.titel")->fetchAll();
    ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Titel</th>
                <th>Författare</th>
                <th>Genre</th>
                <th>Språk</th>
                <th>ISBN</th>
                <th>Åtgärder</th>
            </tr>
        </thead>
        <tbody>
        <!--tabell rad för varje bok i listan-->
            <?php foreach ($all_books as $b): ?>
                <tr>
                    <td><?= $b['bok_id'] ?></td>
                    <td><?= htmlspecialchars($b['titel']) ?></td>
                    <td><?= htmlspecialchars($b['författare']) ?></td>
                    <td><?= htmlspecialchars($b['genre']) ?></td>
                    <td><?= htmlspecialchars($b['språk']) ?></td>
                    <td><?= htmlspecialchars($b['isbn']) ?></td>
                    <td>
                        <button onclick="editBook(<?= $b['bok_id'] ?>)" class="btn-small">Redigera</button>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Är du säker? Detta tar bort boken om inga exemplar finns.');">
                            <input type="hidden" name="bok_id" value="<?= $b['bok_id'] ?>">
                            <button type="submit" name="delete_book" class="btn-small btn-danger">Ta bort</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div id="edit-book-section" style="display:none; margin-top: 2em; padding: 1.5em; background: #f7fafc; border-radius: 8px;">
        <h3>Redigera bok</h3>
        <form method="post" id="edit-book-form">
            <input type="hidden" name="bok_id" id="edit-bok-id">
            <input type="text" name="titel" id="edit-titel" placeholder="Boktitel" required>
            <input type="text" name="author" id="edit-author" placeholder="Författare" required>
            <select name="genre_id" id="edit-genre" required>
                <option value="">Välj genre</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?= $g['genre_id'] ?>"><?= htmlspecialchars($g['namn']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="sprak_id" id="edit-sprak" required>
                <option value="">Välj språk</option>
                <?php foreach ($sprak as $s): ?>
                    <option value="<?= $s['språk_id'] ?>"><?= htmlspecialchars($s['namn']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="isbn" id="edit-isbn" placeholder="ISBN" required>
            <input type="text" name="cover_url" id="edit-cover" placeholder="Omslags-URL (https://...)">
            <button type="submit" name="edit_book">Uppdatera bok</button>
            <button type="button" onclick="cancelEdit()" class="btn-secondary">Avbryt</button>
        </form>
    </div>
    </div>
    
    <!-- Users Tab -->
    <div id="users-tab" class="tab-content">
    <h2>Användare</h2>
    <?php if ($userMsg) echo '<div class="notice success">' . htmlspecialchars($userMsg) . '</div>'; ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Namn</th>
                <th>Email</th>
                <th>Roll</th>
                <th>Skapad</th>
                <th>Åtgärder</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['användare_id']) ?></td>
                    <td><?= htmlspecialchars($user['användare_namn']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['roll_namn']) ?></td>
                    <td><?= htmlspecialchars($user['skapad']) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['användare_id'] ?>">
                            <select name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['roll_id'] ?>" <?= $user['roll_id'] == $role['roll_id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['roll_namn']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="change_role" class="btn-small">Ändra</button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Är du säker på att du vill ta bort denna användare?');">
                            <input type="hidden" name="user_id" value="<?= $user['användare_id'] ?>">
                            <button type="submit" name="remove_user" class="btn-small btn-danger" <?= $user['användare_id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>Ta bort</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    
    <!-- Loans Tab -->
    <div id="loans-tab" class="tab-content">
        <h2>Alla lån</h2>
        <?php
        $statement = $pdo->query("SELECT l.lån_id, u.användare_namn AS användare, b.titel, f.namn AS författare, l.lånedatum, l.förfallodatum, l.återlämnad_datum, l.förseningsavgift FROM lån l JOIN användare u ON l.användare_id = u.användare_id JOIN exemplar e ON l.exemplar_id = e.exemplar_id JOIN bok b ON e.bok_id = b.bok_id JOIN författare f ON b.författare_id = f.författare_id ORDER BY l.lånedatum DESC");
        ?>
        <table>
            <thead>
                <tr>
                    <th>Användare</th>
                    <th>Boktitel</th>
                    <th>Författare</th>
                    <th>Lånedatum</th>
                    <th>Förfallodatum</th>
                    <th>Återlämnad</th>
                    <th>Förseningsavgift</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statement as $loan): ?>
                    <tr>
                        <td><?= htmlspecialchars($loan['användare']) ?></td>
                        <td><?= htmlspecialchars($loan['titel']) ?></td>
                        <td><?= htmlspecialchars($loan['författare']) ?></td>
                        <td><?= htmlspecialchars($loan['lånedatum']) ?></td>
                        <td><?= htmlspecialchars($loan['förfallodatum']) ?></td>
                        <td><?= $loan['återlämnad_datum'] ? htmlspecialchars($loan['återlämnad_datum']) : 'Ej återlämnad' ?></td>
                        <td><?= $loan['förseningsavgift'] ? htmlspecialchars($loan['förseningsavgift']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Settings Tab -->
    <div id="settings-tab" class="tab-content">
    <h2>Lägg till genre</h2>
    <?php if ($genreMsg) echo '<div class="notice success">' . htmlspecialchars($genreMsg) . '</div>'; ?>
    <form method="post">
        <input type="text" name="genre_namn" placeholder="Genre namn" required>
        <button type="submit" name="add_genre">Lägg till genre</button>
    </form>
    
    <h2>Lägg till språk</h2>
    <?php if ($languageMsg) echo '<div class="notice success">' . htmlspecialchars($languageMsg) . '</div>'; ?>
    <form method="post">
        <input type="text" name="sprak_namn" placeholder="Språk namn" required>
        <button type="submit" name="add_language">Lägg till språk</button>
    </form>
    
    <h2>Hantera exemplar</h2>
    <?php if ($exemplarMsg) echo '<div class="notice success">' . htmlspecialchars($exemplarMsg) . '</div>'; ?>
    <h3>Lägg till exemplar</h3>
    <form method="post">
        <select name="bok_id" required>
            <option value="">Välj bok</option>
            <?php foreach ($books_for_exemplar as $book): ?>
                <option value="<?= $book['bok_id'] ?>"><?= htmlspecialchars($book['titel']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" required>
            <option value="available">Tillgänglig</option>
            <option value="loaned">Utlånad</option>
        </select>
        <button type="submit" name="add_exemplar">Lägg till exemplar</button>
    </form>
    
    <h3>Alla exemplar</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Boktitel</th>
                <th>Författare</th>
                <th>Status</th>
                <th>Åtgärder</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exemplars as $ex): ?>
                <tr>
                    <td><?= $ex['exemplar_id'] ?></td>
                    <td><?= htmlspecialchars($ex['titel']) ?></td>
                    <td><?= htmlspecialchars($ex['författare']) ?></td>
                    <td><?= htmlspecialchars($ex['status']) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="exemplar_id" value="<?= $ex['exemplar_id'] ?>">
                            <select name="status" required>
                                <option value="available" <?= $ex['status'] === 'available' ? 'selected' : '' ?>>Tillgänglig</option>
                                <option value="loaned" <?= $ex['status'] === 'loaned' ? 'selected' : '' ?>>Utlånad</option>
                            </select>
                            <button type="submit" name="update_exemplar" class="btn-small">Uppdatera</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    
</div>

<script>
// JavaScript för att hantera redigering av böcker
const bookData = <?= json_encode($all_books) ?>;
const genreData = <?= json_encode($genres) ?>;
const sprakData = <?= json_encode($sprak) ?>;

// Visa redigeringsformuläret för en bok
function editBook(bokId) {
    const book = bookData.find(b => b.bok_id == bokId);
    if (!book) return;
    
    document.getElementById('edit-bok-id').value = book.bok_id;
    document.getElementById('edit-titel').value = book.titel;
    document.getElementById('edit-author').value = book.författare;
    document.getElementById('edit-isbn').value = book.isbn;
    document.getElementById('edit-cover').value = book.cover_url || '';
    
    // Set genre
    const genreOption = genreData.find(g => g.namn === book.genre);
    if (genreOption) {
        document.getElementById('edit-genre').value = genreOption.genre_id;
    }
    
    // Set språk
    const sprakOption = sprakData.find(s => s.namn === book.språk);
    if (sprakOption) {
        document.getElementById('edit-sprak').value = sprakOption.språk_id;
    }
    
    document.getElementById('edit-book-section').style.display = 'block';
    document.getElementById('edit-book-section').scrollIntoView({ behavior: 'smooth' });
}

// Avbryt redigering och stäng formuläret
function cancelEdit() {
    document.getElementById('edit-book-section').style.display = 'none';
    document.getElementById('edit-book-form').reset();
}

// Byt mellan flikar (böcker, användare, lån, inställningar)
function showTab(tabName, element) {
    // Göm alla flikar
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Ta bort aktiv-markering från alla knappar
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Visa vald flik
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Markera klickad knapp som aktiv
    if (element) {
        element.classList.add('active');
    }
    
    // Spara vilken flik som är aktiv (så man kommer tillbaka till samma vid omladdning)
    sessionStorage.setItem('activeAdminTab', tabName);
}

// Återställ senast öppnade fliken när sidan laddas
window.addEventListener('DOMContentLoaded', function() {
    const activeTab = sessionStorage.getItem('activeAdminTab');
    if (activeTab) {
        const tabs = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        document.getElementById(activeTab + '-tab').classList.add('active');
        const targetBtn = Array.from(buttons).find(btn => btn.onclick && btn.onclick.toString().includes(activeTab));
        if (targetBtn) targetBtn.classList.add('active');
    }
});
</script>
</body>
</html>