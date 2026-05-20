<?php
/**
 * Syfte: Startsida med login- och registerformulär + auto-login via rememberme-cookie.
 */
session_start();
if (!isset($_SESSION['loggedin']) && isset($_COOKIE['rememberme'])) {
    $username = $_COOKIE['rememberme'];
    $filename = "users.json";
    $users = [];
    if (file_exists($filename)) {
        $users = json_decode(file_get_contents($filename), true) ?? [];
    }
    $found = false;
    $role = "användare";
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $found = true;
            $role = $user['role'] ?? "användare";
            break;
        }
    }
    if ($found) {
        // Skydda session när man auto-loggar in
        if (function_exists('session_regenerate_id')) { @session_regenerate_id(true); }
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: admin.php");
        exit;
    } else {
        setcookie("rememberme", "", time() - 3600, "/");
    }
}
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggningsapplikation</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../../m02/Favicon-a.jpg" type="image/x-icon">
</head>
<body>
<div class="center-wrap">
  <div>
    <?php
    // Notice: konto raderat
    if (isset($_GET['deleted'])) {
        echo '<div class="notice error" id="top-notice">Ditt konto har raderats.</div>';
    }
    // Notice: fel login-data
    if (isset($_GET['error'])) {
        echo '<div class="notice error" id="top-notice">Fel: Användarnamn eller lösenord är felaktigt.</div>';
    }
    // Notice: skyddad sida utan inloggning
    if (isset($_GET['noaccess'])) {
        echo '<div class="notice warning" id="top-notice">Du har inte behörighet att komma åt den sidan.</div>';
    }
    // Notice: feedback efter registrering
    if (isset($_GET['regsuccess'])) {
        echo '<div class="notice success" id="top-notice">Konto skapat! Du kan nu logga in.</div>';
    }
    if (isset($_GET['regerror'])) {
        if ($_GET['regerror'] === 'dupecombo') {
            $msg = 'Det finns redan ett konto med samma användarnamn och lösenord.';
        } elseif ($_GET['regerror'] === 'upptaget') {
            $msg = 'Användarnamnet är redan upptaget.';
        } else {
            $msg = 'Fyll i alla fält!';
        }
        echo '<div class="notice error" id="top-notice">' . $msg . '</div>';
    }
    ?>
  </div>
  <div id="frm1">
    <form action="login.php" method="post">
        <h3>Hej<span class="sp">,</span> välkommen & Logga In</h3>
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
    <form action="register.php" method="post">
        <h3>Skapa nytt konto</h3>
        <input type="text" name="new_username" placeholder="Nytt användarnamn" required>
        <input type="password" name="new_password" placeholder="Nytt lösenord" required>
        <button type="submit">Skapa konto</button>
    </form>
</div>
</body>
</html>
