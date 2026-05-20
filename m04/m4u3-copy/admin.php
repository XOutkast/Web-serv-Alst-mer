<?php
/**
 * Syfte: Skyddad användarsida efter inloggning. Visar grundinfo + enkel gästbok (senaste N inlägg) + kontohantering.
 * Innehåll: Lista / skapa / redigera / ta bort gästboksinlägg, länkar för logout, byt lösenord, radera konto och ääkerhet som att Kräva inloggning (require check_login.php). CSRF-token används för alla POST-åtgärds.
 */
require "check_login.php";              // säkerställer att session & role finns
date_default_timezone_set('Europe/Stockholm'); // svensk tidszon för datum & tid

// CSRF: generera token om den saknas
if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$filename = "guestbook.json";
$entries = [];
if (file_exists($filename)) {
    $json = file_get_contents($filename);
    $entries = json_decode($json, true) ?? [];
}
$total = count($entries);
$maxEntries = 3;
$shown = 0;

// POST-hantering för gästboken (lägg till, ta bort, redigera) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF-kontroll – alla POST måste bära korrekt token
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        header('Location: admin.php');
        exit;
    }
    // Lägg till nytt inlägg (append sist i listan)
    if (isset($_POST['message']) && isset($_SESSION['username'])) {
        $msg = trim($_POST['message']);
        $sessionName = $_SESSION['username'];
        if ($msg !== '') {
            $entry = [
                "date" => date('Y-m-d H:i'),
                "username" => $sessionName,
                "message" => $msg
            ];
            $entries[] = $entry; // lägg sist
            // Skriv säkert till fil med lås
            file_put_contents($filename, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            header('Location: admin.php?success=1');
            exit;
        }
    }

    // Ta bort inlägg (bara ägare eller admin kan göra det)
    if (isset($_POST['delete_entry']) && isset($_SESSION['username'])) {
        $displayIndex = intval($_POST['delete_entry']);
        // Eftersom jag använder array_reverse vid visning måste man räkna ut rätt index
        $realIndex = $total - 1 - $displayIndex; // mappa från visningsindex (reverse) till riktigt index
        if (isset($entries[$realIndex])) {
            $entry = $entries[$realIndex];
            if (
                ($entry['username'] === $_SESSION['username'])
                || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
            ) {
                array_splice($entries, $realIndex, 1); // ta bort exakt det inlägget
                file_put_contents($filename, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                header('Location: admin.php');
                exit;
            }
        }
    }

    // Förbered redigering (visa edit-formulär i render-steget)
    if (isset($_POST['edit_entry']) && isset($_SESSION['username'])) {
        // Ingen redirect, edit-formuläret visas i HTML-delen
    }

    // Spara redigerat inlägg ( alltså persist ändring)
    if (isset($_POST['save_edit']) && isset($_POST['edit_entry']) && isset($_POST['edit_message']) && isset($_SESSION['username'])) {
        $displayIndex = intval($_POST['edit_entry']);
        $realIndex = $total - 1 - $displayIndex;
        $newMsg = trim($_POST['edit_message']);
        if (isset($entries[$realIndex])) {
            $entry = $entries[$realIndex];
            if (
                ($entry['username'] === $_SESSION['username'])
                || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
            ) {
                $entries[$realIndex]['message'] = $newMsg;
                file_put_contents($filename, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                header('Location: admin.php');
                exit;
            }
        }
    }
    // Efter POST, ladda om entries
    if (file_exists($filename)) {
        $json = file_get_contents($filename);
        $entries = json_decode($json, true) ?? [];
    }
    $total = count($entries);
}
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Adminsida</title>
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="infowall">
    <h2>HEJ & VÄLKOMMEN <?php echo strtoupper(htmlspecialchars($_SESSION['username'])); ?>!</h2>
    <ul>
    <!-- Kort info) -->
        <li>Välkommen till din sida.</li>
        <li>Här kan du logga ut, byta lösenord och radera konto.</li>
        <li>Du kan också skriva ett meddelande i gästboken.</li>
    </ul>
    <div class="admin-welcome">
        <h3>Du är nu inloggad<span class="sp">, </span>välkommen</h3>
        <p>Din roll: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p>
        <div class="admin-actions">
            <a href="logout.php">Logga ut</a>
            <a href="change_password.php">Byt lösenord</a>
            <form action="delete_user.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ($_SESSION['csrf']=bin2hex(random_bytes(16)))); ?>">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                <button type="submit" onclick="return confirm('Vill du verkligen radera ditt konto?');">Radera mitt konto</button>
            </form>
        </div>
    </div>

    <!-- Gästbok (senaste <?php echo $maxEntries; ?> inlägg) -->
    <div class="guestbook-wrap">
        <h2>Gästbok</h2>
        <?php
        if (isset($_GET['success']) && $_GET['success'] == '1') {
            echo '<div class="notice success" id="sparat">Inlägget är sparat!</div>';
        }
        ?>
        <!-- Formulär: lägg till nytt inlägg i gästboken -->
        <form method="post">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
            <textarea name="message" placeholder="Skriv ett meddelande..." required></textarea>
            <button type="submit">Skicka</button>
        </form>
        <div class="guestbook-entries">
            <?php
            foreach (array_reverse($entries) as $displayIndex => $entry) {
                if ($shown >= $maxEntries) break;
                $date = $entry['date'];
                $username = $entry['username'];
                $message = $entry['message'];
                echo '<div class="guestbook-entry">';
                echo '<div style="font-weight:600;color:#7c5a5a;">' . htmlspecialchars($username) . '</div>';
                echo '<div style="font-size:0.95em;color:#888;">' . htmlspecialchars($date) . '</div>';

                // Visa gamla meddelandet överför även när använderna redigerar den
                echo '<div style="display:block;max-width:100%;word-break:break-word;overflow-wrap:break-word;">' . htmlspecialchars($message) . '</div>';

                // Om rätt entry är vald för redigering, visa edit-formulär
                if (
                    isset($_POST['edit_entry']) &&
                    $_POST['edit_entry'] == $displayIndex &&
                    (
                        (isset($_SESSION['username']) && $_SESSION['username'] === $username)
                        || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
                    )
                ) {
                    echo '<form method="post" class="edit-form" style="display:block;margin-bottom:6px;">';
                    echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">';
                    echo '<input type="hidden" name="edit_entry" value="' . $displayIndex . '">';
                    echo '<textarea name="edit_message" style="width:90%;min-height:50px;">' . htmlspecialchars($message) . '</textarea>';
                    echo '<button type="submit" name="save_edit" class="gb-btn gb-save">Spara</button>';
                    echo '</form>';
                } else {
                    // Redigera/ta bort-knappar
                    if (
                        (isset($_SESSION['username']) && $_SESSION['username'] === $username)
                        || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
                    ) {
                        echo '<div class="gb-btn-group" style="display:flex;gap:8px;margin-top:6px;">';
                                echo '<form method="post" style="display:inline;">'
                                    .'<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'
                           .'<input type="hidden" name="delete_entry" value="' . $displayIndex . '">'
                           .'<button type="submit" class="gb-btn gb-delete" title="Ta bort"> Ta bort</button>'
                           .'</form>';
                                echo '<form method="post" style="display:inline;">'
                                    .'<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'
                           .'<input type="hidden" name="edit_entry" value="' . $displayIndex . '">'
                           .'<button type="submit" class="gb-btn gb-edit" title="Redigera"> Redigera</button>'
                           .'</form>';
                        echo '</div>';
                    }
                }
                echo '</div>';
                $shown++;
            }
            ?>
        </div>
        <?php if ($total > 3): ?>
            <div style="text-align:center;">
                <a href="guestbook.php" class="back-link">Visa alla inlägg</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
  var notice = document.getElementById('sparat');
  if (notice) {
    setTimeout(function() {
      notice.style.display = 'none';
    }, 1500); // 1.5 seconds
  }
});
</script>

</body>
</html>
