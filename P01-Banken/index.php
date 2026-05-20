<?php
session_start();
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Europe/Stockholm');
}

// Auto-inloggning från cookie
if (!isset($_SESSION['loggedin']) && isset($_COOKIE['username'])) {
    // Verifiera att användaren finns i users.json
    require_once 'json_store.php';
    $users = load_users();
    $userExists = false;
    $userRole = 'user';
    
    foreach ($users as $u) {
        if (($u['username'] ?? null) === $_COOKIE['username']) {
            $userExists = true;
            $userRole = $u['role'] ?? 'user';
            break;
        }
    }
    
    if ($userExists) {
        $_SESSION['username'] = $_COOKIE['username'];
        $_SESSION['role'] = $userRole;
        $_SESSION['loggedin'] = true;
        header("Location: admin.php");
        exit;
    }
}

// Redirigera om redan inloggad
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
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
        //   Konto raderat (meddelande)
        if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
            echo '<div class="notice error" id="top-notice">Ditt konto har raderats.</div>';
        }

        //   Registrering klar (meddelande)
        if (isset($_GET['success']) && $_GET['success'] === 'registered') {
            echo '<div class="notice success" id="top-notice">Konto skapat! Du kan nu logga in.</div>';
        }

        //   FEL: Visa kort feltext baserat på query
        if (isset($_GET['error'])): ?>
            <div class="notice error" id="top-notice">
                <?php
                if ($_GET['error'] === 'locked') {
                    echo "Kontot är låst på grund av för många misslyckade inloggningar. Kontakta admin.";
                } elseif ($_GET['error'] === 'invalid') {
                    echo "Felaktigt användarnamn eller lösenord.";
                } elseif ($_GET['error'] === 'exists') {
                    echo "Användarnamnet finns redan.";
                } elseif ($_GET['error'] === 'empty') {
                    echo "Fyll i alla fält.";
                } else {
                    echo "Ett fel uppstod. Försök igen.";
                }
                ?>
            </div>
        <?php endif;

        //   Otillåten åtkomst
        if (isset($_GET['noaccess'])) {
            echo '<div class="notice warning" id="top-notice">Du har inte behörighet att komma åt den sidan.</div>';
        }
        ?>
    </div>

    <div id="frm1">
        <!-- Inloggningsformulär -->
        <form action="auth.php?action=login" method="post">
            <h3 style="font-size: 1.6rem"> Logga In</h3>
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
        <!-- Registreringsformulär (skapar startsaldo 1000 kr via transaktion) -->
        <form action="auth.php?action=register" method="post">
            <h3>Skapa nytt konto</h3>
            <input type="text" name="username" placeholder="Nytt användarnamn" required>
            <input type="password" name="password" placeholder="Nytt lösenord" required>
            <button type="submit">Skapa konto</button>
        </form>
    </div>
</div>
</body>
</html>