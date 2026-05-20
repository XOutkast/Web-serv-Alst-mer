<?php
require "check_login.php";
require_once 'config.php';

// Kontrollera att användaren är admin
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: admin.php?error=no_permission");
    exit;
}

global $pdo;

// Ta bort användare om requested
if (isset($_GET['delete_user'])) {
    $deleteUserId = (int)$_GET['delete_user'];
    
    // Förhindra att admin raderar sig själv
    if ($deleteUserId === $_SESSION['user_id']) {
        $_SESSION['error'] = "Du kan inte radera dig själv!";
    } else {
        $Qstmts = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $Qstmts->execute([$deleteUserId]);
        $_SESSION['message'] = "Användare raderad";
    }
    header("Location: admin_panel.php");
    exit;
}

// Hämta alla användare
$Qstmts = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.role, u.created_at, u.last_login,
           COUNT(a.id) as account_count
    FROM users u
    LEFT JOIN accounts a ON u.id = a.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$Qstmts->execute();
$allUsers = $Qstmts->fetchAll();

// Beräkna statistik
$totalUsers = count($allUsers);
$Qstmts = $pdo->query("SELECT COUNT(*) as total FROM accounts");
$totalAccounts = $Qstmts->fetch()['total'];
$Qstmts = $pdo->query("SELECT COUNT(*) as total FROM transactions");
$totalTransactions = $Qstmts->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
<header>
    <nav>
        <h1>Admin Panel</h1>
        <div class="nav-links">
            <a href="admin.php" class="btn-secondary">Mitt Konto</a>
            <a href="auth.php?action=logout" class="btn-log">Logga ut</a>
        </div>
    </nav>
</header>

<?php if (isset($_SESSION['message'])): ?>
    <div class="notice success">
        <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="notice error">
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<main class="container">
    <!-- Statistik -->
    <section class="accounts-section">
        <h2>Översikt</h2>
        <div class="accounts-grid">
            <div class="account-card">
                <h3>Användare</h3>
                <p class="balance"><?= $totalUsers ?></p>
            </div>
            <div class="account-card">
                <h3>Totalt Konton</h3>
                <p class="balance"><?= $totalAccounts ?></p>
            </div>
            <div class="account-card">
                <h3>Transaktioner</h3>
                <p class="balance"><?= $totalTransactions ?></p>
            </div>
        </div>
    </section>

    <!-- Användarlista -->
    <section class="transactions-section">
        <h2>Alla Användare</h2>
        <div class="tx-table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Användarnamn</th>
                    <th>E-post</th>
                    <th>Roll</th>
                    <th>Konton</th>
                    <th>Registrerad</th>
                    <th>Senaste login</th>
                    <th>Åtgärd</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($allUsers as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= $user['account_count'] ?></td>
                        <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                        <td><?= $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Aldrig' ?></td>
                        <td>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <a href="admin_panel.php?delete_user=<?= $user['id'] ?>" 
                                   onclick="return confirm('Är du säker på att du vill radera <?= htmlspecialchars($user['username']) ?>?')"
                                   style="color: red;">Radera</a>
                            <?php else: ?>
                                <em>Du</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var notice = document.querySelector('.notice.success, .notice.error');
        if (notice) {
            setTimeout(function () {
                notice.style.opacity = '0';
                setTimeout(() => notice.remove(), 500);
            }, 2000);
        }
    });
</script>
</body>
</html>
