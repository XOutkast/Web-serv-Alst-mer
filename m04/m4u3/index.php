<?php
session_start();
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
    // Notice: feedback efter registrering
    if (isset($_GET['regsuccess'])) {
        echo '<div class="notice success" id="top-notice">Konto skapat! Du kan nu logga in.</div>';
    }
    if (isset($_GET['regerror'])) {
        if ($_GET['regerror'] === 'upptaget') {
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
        <h3 style = "font-size: 1.6rem;">Logga In</h3>
        <input type="text" name="username" placeholder="Användarnamn" required>
        <input type="password" name="password" placeholder="Lösenord" required>
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
