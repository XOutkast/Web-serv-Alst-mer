<?php
session_start();

// Om användaren redan är inloggad via session, skicka direkt till admin.php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: admin.php");
    exit;
}

// Om användaren har en giltig "rememberme"-cookie, logga in automatiskt och skicka till admin.php
if (isset($_COOKIE['rememberme'])) {
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
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: admin.php");
        exit;
    } else {
        setcookie("rememberme", "", time() - 3600, "/");
    }
}

// Visa felmeddelande om användarnamn/lösenord är fel
if (isset($_GET['error'])) {
    echo '<div style="max-width:350px;margin:20px auto 0 auto;padding:12px 18px;background:#ffeaea;color:#a94442;border:1.5px solid #f5c6cb;border-radius:6px;text-align:center;font-weight:500;box-shadow:0 2px 8px rgba(0,0,0,0.04);">';
    echo 'Fel: Användarnamnet eller lösenordet är felaktigt.';
    echo '</div>';
}

// Visa meddelande om någon försökte gå till en skyddad sida utan att vara inloggad
if (isset($_GET['noaccess'])) {
    echo "<p style='color:red; text-align:center;'>Du har inte behörighet att komma åt den sidan.</p>";
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
</div>
</body>
</html>
