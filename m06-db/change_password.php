<?php
session_start();
require_once 'check_login.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hämta fält från formuläret
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Kontrollera att alla fält är ifyllda
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: change_password.php?error=empty");
        exit;
    }

    // Kontrollera att nya lösenorden matchar
    if ($newPassword !== $confirmPassword) {
        header("Location: change_password.php?error=mismatch");
        exit;
    }
    global $pdo;
    // Hämta hashat lösenord från databasen
    $Qstmts = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $Qstmts->execute([$_SESSION['user_id']]);
    $user = $Qstmts->fetch();

    // Verifiera nuvarande lösenord
    if (!password_verify($currentPassword, $user['password_hash'])) {
        header("Location: change_password.php?error=wrong");
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Uppdatera lösenord och ta bort "måste byta lösenord"-flagga
    $Qstmts = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = FALSE WHERE id = ?");
    $Qstmts->execute([$hashedPassword, $_SESSION['user_id']]);

    unset($_SESSION['must_change_password']);

    header("Location: admin.php?success=password_changed");
    exit;

};

// Kontrollera om lösenordsbyte krävs
$isRequired = isset($_GET['required']) || isset($_SESSION['must_change_password']);
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ändra lösenord</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="pwchange-wrap">
    <h2>Ändra lösenord</h2>

    <?php if ($isRequired): ?>
        <div class="notice warning">
            Du måste byta lösenord innan du kan fortsätta.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice error">
            <?php
            if ($_GET['error'] === 'empty') {
                echo "Fyll i alla fält!";
            } elseif ($_GET['error'] === 'mismatch') {
                echo "Nya lösenorden matchar inte!";
            } elseif ($_GET['error'] === 'wrong') {
                echo "Felaktigt nuvarande lösenord!";
            } else {
                echo "Ett fel uppstod. Försök igen.";
            }
            ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="password" name="current_password" placeholder="Nuvarande lösenord" required>
        <input type="password" name="new_password" placeholder="Nytt lösenord" required>
        <input type="password" name="confirm_password" placeholder="Bekräfta nytt lösenord" required>
        <button type="submit">Ändra lösenord</button>
    </form>

    <?php if (!$isRequired): ?>
        <p style="text-align: center; margin-top: 15px;">
            <a href="admin.php" style="color: #3b82f6; text-decoration: none;">← Tillbaka till kontrollpanelen</a>
        </p>>
    <?php endif; ?>

</body>
</html>
