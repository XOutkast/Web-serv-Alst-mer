<?php
//Startar en PHP-session
session_start();
// Om användaren skickar in ett namn via formuläret
if (isset($_POST['namn']) && !empty($_POST['namn'])) {
    $_SESSION['namn'] = $_POST['namn'];
}

// Om användaren klickar på "logga ut"-länken
if (isset($_GET['logout'])) {
    // Förstör sessionen
    session_destroy();
    session_start(); // starta om sessionen
    echo "<p>Det finns ingen användare!</p>";
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
<h2>Formulär med session</h2>

<!--En form som man skikca sitt nman sen skickar in-->
<form method="post">
    <label for="namn">Skriv ditt namn:</label>
    <input type="text" id="namn" name="namn">
    <button type="submit">Skicka</button>
</form>

<!--Anchor-tag länken (button) för att ladda om sidan och döda sessionen-->
<p><a href="?">Ladda om sidan</a></p>
<p><a href="?logout=1">Döda sessionen</a></p>

<?php
// Visa namnet om det finns sparat
if (isset($_SESSION['namn'])) {
    echo "<p>Hej, ". htmlspecialchars($_SESSION['namn']) . "! Välkommen</p>";
} elseif (!isset($_GET['logout'])) {
    echo "<p>Du är inte en användare!</p>";
}
?>
</body>
</html>
