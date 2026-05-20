<?php

// Starta session om den är inte redan startad
if (session_status() === PHP_SESSION_NONE) {
    // Session-ssäkerhets-inställningar -
    ini_set('session.cookie_httponly', 1);  // Förhindra JS-åtkomst till cookies (alltså XSS-skydd)
    ini_set('session.use_only_cookies', 1); // Skicka sessions-ID endast via cookies (inte URL) (skydd mot session fixation)
    ini_set('session.cookie_secure', 0);    // Skicka cookies endast över HTTPS
    
    session_start();
}

// Hämta användarens roll om inloggad
$userRole = $_SESSION['role'] ?? '';
if (isset($_SESSION['user_id']) && empty($userRole)) {
    require_once 'db_cnnt.php';
    global $pdo;
    $statement = $pdo->prepare("SELECT r.roll_namn FROM användare a JOIN roll r ON a.roll_id = r.roll_id WHERE a.användare_id = ?");
    $statement->execute([$_SESSION['user_id']]);
    $row = $statement->fetch();
    if ($row) {
        $userRole = $row['roll_namn'];
        $_SESSION['role'] = $userRole; // Cacha i session  
    }
}
?>
<header>
    <nav class="navbar">
        <ul>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="home.php">Hem</a></li>
                <li><a href="search.php">Sök</a></li>
                <li><a href="my_loans.php">Mina lån</a></li>
                <?php if ($userRole === 'Admin'): ?>
                    <li><a href="admin.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="account.php">Mitt konto</a></li>
                <li><a href="account.php?action=logout">Logga ut</a></li>
            <?php else: ?>
                <li><a href="index.php">Logga in</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
