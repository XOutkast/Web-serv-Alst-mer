<?php
session_start();
require_once 'config.php';

// Automatisk inloggning via cookie om användaren inte är inloggad
if (!isset($_SESSION['user_id']) && isset($_COOKIE['username'])) {
    // Verifiera att användaren finns i databasen
    global $pdo;
    $Qstmts = $pdo->prepare("SELECT id, username, role FROM users WHERE username = ?");
    $Qstmts->execute([$_COOKIE['username']]);
    $user = $Qstmts->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['loggedin'] = true;
        header("Location: admin.php");
        exit;
    }
}

// Om användaren redan är inloggad, skicka till admin-sidan
if (isset($_SESSION['user_id'])) {
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggningsapplikation</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../m02/Favicon-a.jpg" type="image/x-icon">
</head>
<body>
<div class="center-wrap">
    <div>
        <?php
        // Meddelande: Konto raderat
        if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
            echo '<div class="notice error" id="top-notice">Ditt konto har raderats.</div>';
        }

        // Meddelande: Registrering klar
        if (isset($_GET['success']) && $_GET['success'] === 'registered') {
            echo '<div class="notice success" id="top-notice">Konto skapat! Du kan nu logga in.</div>';
        }

        // Felmeddelande
        if (isset($_GET['error'])): ?>
            <div class="notice error" id="top-notice">
                <?php
                if ($_GET['error'] === 'locked') {
                    echo "Kontot är låst på grund av för många misslyckade inloggningar. Kontakta admin.";
                } elseif ($_GET['error'] === 'invalid') {
                    echo "Felaktigt användarnamn eller lösenord.";
                } elseif ($_GET['error'] === 'exists') {
                    echo "Användarnamnet eller e-postadressen finns redan.";
                } elseif ($_GET['error'] === 'email') {
                    echo "Ogiltig e-postadress.";
                } elseif ($_GET['error'] === 'empty') {
                    echo "Fyll i alla fält.";
                } else {
                    echo "Ett fel uppstod. Försök igen.";
                }
                ?>
            </div>
        <?php endif;

        // Ingen behörighet
        if (isset($_GET['noaccess'])) {
            echo '<div class="notice warning" id="top-notice">Du har inte behörighet att komma åt den sidan.</div>';
        }
        ?>
    </div>

    <div id="frm1">
        <!-- Inloggningsformulär -->
        <form action="auth.php?action=login" method="post">
            <h3>Hej<span class="sp">,</span> Välkommen & Logga In</h3>
            <input type="text" name="username" placeholder="Användarnamn" required>
            <input type="password" name="password" placeholder="Lösenord" required>

            <div class="chk-wrap">
                <label for="chk">
                    <input type="checkbox" name="remember" id="chk">
                    <span class="check">Håll mig inloggad</span>
                </label>
            </div>

            <button type="submit">Login</button>
        </form>

        <hr>

        <!-- Registreringsformulär -->
        <form action="auth.php?action=register" method="post">
            <h3>Skapa nytt konto</h3>
            <input type="text" name="username" placeholder="Nytt användarnamn" required>
            <input type="email" name="email" placeholder="E-post" required>
            <input type="password" name="password" placeholder="Nytt lösenord" required>
            <button type="submit">Skapa konto</button>
        </form>
    </div>
</div>
</body>
</html>