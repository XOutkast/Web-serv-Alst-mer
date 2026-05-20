<?php
session_start(); // Startar sessionen

// Visar felmeddelande om användarnamn eller lösenord är fel
if (isset($_GET['error'])) {
    echo '<div style="max-width:350px;margin:20px auto 0 auto;padding:12px 18px;background:#ffeaea;color:#a94442;border:1.5px solid #f5c6cb;border-radius:6px;text-align:center;font-weight:500;box-shadow:0 2px 8px rgba(0,0,0,0.04);">';
    echo 'Fel: Användarnamnet eller lösenordet är felaktigt.';
    echo '</div>';
}   

// Visar meddelande om någon försökte gå till en skyddad sida utan att vara inloggad
if (isset($_GET['noaccess'])) {
    echo '<div style="max-width:350px;margin:20px auto 0 auto;padding:12px 18px;background:#fff3cd;color:#856404;border:1.5px solid #ffeeba;border-radius:6px;text-align:center;font-weight:500;box-shadow:0 2px 8px rgba(0,0,0,0.04);">';
    echo 'Du har inte behörighet att komma åt den sidan.';
    echo '</div>';
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
                <input type="checkbox">
                <span class="check">Håll mig inloggad</span>
            </label>
        </div>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
