<?php
$firstname = isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : '';
$lastname = isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : '';
$birthdate = isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : '';
$password = isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '';
$gender = isset($_POST['gender']) ? htmlspecialchars($_POST['gender']) : '';
$terms = isset($_POST['terms']) ? 'Godkänt' : 'Ej godkänt';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmälan mottagen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Registrering mottagen</h1>

    <div class="info-group">
        <label>Förnamn:</label>
        <p><?php echo $firstname; ?></p>
    </div>
    <div class="info-group">
        <label>Efternamn:</label>
        <p><?php echo $lastname; ?></p>
    </div>
    <div class="info-group">
        <label>Födelsedatum:</label>
        <p><?php echo $birthdate; ?></p>
    </div>

    <?php
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
    ?>
    <div class="info-group">
        <label>E-post:</label>
        <p><?php echo $email; ?></p>
    </div>

    <div class="info-group">
        <label>Lösenord:</label>
        <p><?php echo str_repeat('*', strlen($password)); ?></p>
    </div>
    <div class="info-group">
        <label>Kön:</label>
        <p><?php echo $gender; ?></p>
    </div>
    <div class="info-group">
        <label>Användarvillkor:</label>
        <p><?php echo $terms; ?></p>
    </div>

    <a href="m03u01.php" class="back-button">Tillbaka till formuläret</a>
</div>
</body>
</html>
