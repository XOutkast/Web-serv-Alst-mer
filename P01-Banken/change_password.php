<?php
session_start();
// Sätt lokal tidszon
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Europe/Stockholm');
}
// inkludera de filer
require_once 'check_login.php'; // kontrollera om användaren är inloggad
require_once 'json_store.php';


// Om formuläret skickats via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// 1) Läs in lösenordsfält via POST
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

// 2) Validering; kontrollera att alla fält är ifyllda
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: change_password.php?error=empty");
        exit;
    }
// Kontrollera att nytt lösenord matchar bekräftelsen
    if ($newPassword !== $confirmPassword) {
        header("Location: change_password.php?error=mismatch");
        exit;
    }


// 3) Hämta aktuell användare från session och users.json
    $uname = $_SESSION['username'] ?? null;
    if (!$uname) {
        header("Location: index.php?noaccess=1");
        exit;
    }

    $users = load_users();
    $foundIdx = null;
// Leta upp aktuell användare i arrayen
    foreach ($users as $index => $user) {
        if (($user['username'] ?? null) === $uname) {
            $foundIdx = $index;
            break;
        }
    }
    if ($foundIdx === null) {
        header("Location: index.php?noaccess=1");
        exit;
    }

    $user = $users[$foundIdx];
// 4) Verifiera nuvarande lösenord
    if (!isset($user['password']) || !password_verify($currentPassword, $user['password'])) {
        header("Location: change_password.php?error=wrong");
        exit;
    }

// 5) Hasha och spara nytt lösenord i users.json
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $users[$foundIdx]['password'] = $hashedPassword;

    if (!save_users($users)) {
        header("Location: change_password.php?error=unknown");
        exit;
    }

// 6) Ta bort ev. flagga (om den finns i sessionen)
    unset($_SESSION['must_change_password']);

// lyckad ändring > skicka till admin.php
    header("Location: admin.php?success=password_changed");
    exit;
}


// Kontrollera om lösenordsbyte är obligatoriskt (flagga i GET eller session)
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
        </p>
    <?php endif; ?>
</div>
</body>
</html>