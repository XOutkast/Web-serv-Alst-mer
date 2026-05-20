<?php
// Startar en PHP-session
session_start();

// Om användaren skickar in ett namn via formuläret
if (isset($_POST['namn']) && !empty($_POST['namn'])) {
    $_SESSION['namn'] = $_POST['namn'];
    // Spara namnet i en cookie som gäller i 1 timme
    setcookie("namn", $_POST['namn'], time() + 3600, "/");
}

// Om användaren klickar på "logga ut"-länken (så dödas sessionen men cookien finns kvar)
if (isset($_GET['logout'])) {
    session_destroy();
    session_start(); // starta om sessionen
    echo "<p>Sessionen är avslutad, men cookien finns kvar.</p>";
}

// Om användaren klickar på "radera cookie"
if (isset($_GET['delete_cookie'])) {
    // Radera en cookie
    setcookie("namn", "", time() - 3600, "/");
    echo "<p>Cookien har tagits bort!</p>";
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Datapersistens</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="icon" type="image/png" href="../m02/Favicon-a.jpg"/>
</head>
<body>
<h2>Formulär med session & cookie</h2>

<!--En form som man kan skicka sitt namn via-->
<form method="post">
    <label for="namn">Skriv ditt namn:</label>
    <!-- Fill i fältet med namnet från cookien om det finns -->
    <input type="text" id="namn" name="namn"
           value="<?php echo isset($_COOKIE['namn']) ? htmlspecialchars($_COOKIE['namn']) : ''; ?>">
    <button type="submit">Skicka</button>
</form>

<!--Länkar för olika funktioner (buttons) som raderar cookien-->
<p><a href="?">Ladda om sidan</a></p>
<p><a href="?logout=1">Döda sessionen</a></p>
<p><a href="?delete_cookie=1">Radera cookien</a></p>

<?php
// Visa namnet om det finns i sessionen
if (isset($_SESSION['namn'])) {
    echo "<p>Hej, ". htmlspecialchars($_SESSION['namn']) . "! (från session)</p>";
}
// Om sessionen är borta men cookien finns kvar
elseif (isset($_COOKIE['namn'])) {
    echo "<p>Hej, ". htmlspecialchars($_COOKIE['namn']) . "! (från cookie)</p>";
}
// Om inget finns
else {
    echo "<p>Du är inte en användare!</p>";
}
?>
</body>
</html>
