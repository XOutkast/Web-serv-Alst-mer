<?php
require "check_login.php";
require_once 'config.php';

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

global $pdo;

// Hämta alla konton för användaren
    $Qstmts = $pdo->prepare("
        SELECT id, account_name, balance, created_at 
        FROM accounts 
        WHERE user_id = ? 
        ORDER BY created_at ASC
    ");
    $Qstmts->execute([$userId]);
    $accounts = $Qstmts->fetchAll(PDO::FETCH_ASSOC);

    // Bygg SQL-fråga med filter
    $sql = "
        SELECT t.id, t.type, t.amount, t.date, a.account_name
        FROM transactions t
        JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ?";
    
    $params = [$userId];
    
    // Filtrera efter konto
    if (!empty($_GET['account'])) {
        $sql .= " AND a.account_name = ?";
        $params[] = $_GET['account'];
    }
    
    // Filtrera efter typ
    if (!empty($_GET['type'])) {
        $sql .= " AND t.type = ?";
        $params[] = $_GET['type'];
    }
    
    $sql .= " ORDER BY t.date DESC LIMIT 50";
    
    $Qstmts = $pdo->prepare($sql);
    $Qstmts->execute($params);
    $filteredTransactions = $Qstmts->fetchAll(PDO::FETCH_ASSOC);

    // Beräkna totalt saldo
    $totalBalance = array_sum(array_column($accounts, 'balance'));
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Min Bank</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-style.css">
    <link rel="icon" href="../m02/Favicon-a.jpg" type="image/x-icon">
</head>
<body>
<header>
    <nav>
        <h1>Välkommen, <?= htmlspecialchars($username) ?></h1>
        <div class="nav-links">
            <a href="change_password.php" class="btn-secondary">Byt Lösenord</a>
            <a href="auth.php?action=logout" class="btn-log">Logga ut</a>
        </div>
    </nav>
</header>

<!-- MEDDELANDE-->
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

<!-- GET-parameter meddelanden -->
<?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_amount'): ?>
    <div class="notice error">Ogiltigt belopp.</div>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'insufficient'): ?>
    <div class="notice error">Otillräckligt saldo.</div>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalid_request'): ?>
    <div class="notice error">Ogiltig begäran. Försök igen.</div>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'same_account'): ?>
    <div class="notice error">Kan inte överföra till samma konto.</div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="notice success">Transaktionen är sparad.</div>
<?php endif; ?>

<main class="container">
    <!-- Kontoöversikt -->
    <section class="accounts-section">
        <h2>Mina Konton</h2>
        <div class="accounts-grid" id="accountsGrid">
            <?php foreach ($accounts as $account): ?>
                <div class="account-card" data-account="<?= htmlspecialchars($account['account_name']) ?>">
                    <h3><?= htmlspecialchars($account['account_name']) ?></h3>
                    <p class="balance"><?= number_format($account['balance'], 2, ',', ' ') ?> kr</p>
                    <small>Skapat: <?= date('Y-m-d', strtotime($account['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="total-balance">
            <strong>Totalt saldo:</strong> <?= number_format($totalBalance, 2, ',', ' ') ?> kr
        </div>
    </section>

    <!-- Transaktionsformulär -->
    <section class="actions-section">
        <h2>Hantera Transaktioner</h2>
        <div class="form-tabs">
            <button class="tab-btn active" data-tab="deposit">Insättning</button>
            <button class="tab-btn" data-tab="withdraw">Uttag</button>
            <button class="tab-btn" data-tab="transfer">Överföring</button>
            <button class="tab-btn" data-tab="newaccount">Nytt Konto</button>
        </div>

        <!-- Insättning -->
        <form action="actions.php?action=deposit" method="post" class="tab-content active" id="deposit">
            <h3>Insättning</h3>
            <select name="account" required>
                <option value="">Välj konto</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="amount" placeholder="Belopp" step="0.01" min="0.01" required>
            <button type="submit">Sätt in</button>
        </form>

        <!-- Uttag -->
        <form action="actions.php?action=withdraw" method="post" class="tab-content" id="withdraw">
            <h3>Uttag</h3>
            <select name="account" required>
                <option value="">Välj konto</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="amount" placeholder="Belopp" step="0.01" min="0.01" required>
            <button type="submit">Ta ut</button>
        </form>

        <!-- Överföring -->
        <form action="actions.php?action=transfer" method="post" class="tab-content" id="transfer">
            <h3>Överföring mellan mina konton</h3>
            <select name="from_account" required>
                <option value="">Från konto</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="to_account" required>
                <option value="">Till konto</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="amount" placeholder="Belopp" step="0.01" min="0.01" required>
            <button type="submit">Överför</button>
        </form>

        <!-- Nytt konto -->
        <form action="actions.php?action=open-account" method="post" class="tab-content" id="newaccount">
            <h3>Skapa nytt konto</h3>
            <input type="text" name="account_name" placeholder="Kontonamn (t.ex. Sparkonto)" required>
            <button type="submit">Skapa konto</button>
        </form>
    </section>

    <!-- Transaktionshistorik -->
    <section class="transactions-section">
        <h2>Transaktionshistorik</h2>
        <form method="get" action="admin.php" class="filter-controls" style="margin-bottom: 1em;">
            <select name="account">
                <option value="">Alla konton</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['account_name']) ?>" <?= (($_GET['account'] ?? '') === $acc['account_name']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['account_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="type">
                <option value="">Alla typer</option>
                <option value="deposit" <?= (($_GET['type'] ?? '') === 'deposit') ? 'selected' : '' ?>>Insättning
                </option>
                <option value="withdraw" <?= (($_GET['type'] ?? '') === 'withdraw') ? 'selected' : '' ?>>Uttag</option>
                <option value="transfer-in" <?= (($_GET['type'] ?? '') === 'transfer-in') ? 'selected' : '' ?>>
                    Överföring in
                </option>
                <option value="transfer-out" <?= (($_GET['type'] ?? '') === 'transfer-out') ? 'selected' : '' ?>>
                    Överföring ut
                </option>
                <option value="account-open" <?= (($_GET['type'] ?? '') === 'account-open') ? 'selected' : '' ?>>Öppnat
                    konto
                </option>
            </select>
            <button type="submit">Filtrera</button>
        </form>
        <div class="tx-table-wrap">
            <table id="transactionsTable">
                <thead>
                <tr>
                    <th>Datum</th>
                    <th>Konto</th>
                    <th>Typ</th>
                    <th>Belopp</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($filteredTransactions as $t): ?>
                    <tr data-account="<?= htmlspecialchars($t['account_name']) ?>"
                        data-type="<?= htmlspecialchars($t['type']) ?>">
                        <td><?= date('Y-m-d H:i', strtotime($t['date'])) ?></td>
                        <td><?= htmlspecialchars($t['account_name']) ?></td>
                        <td><?= htmlspecialchars($t['type']) ?></td>
                        <td class="<?= $t['amount'] < 0 ? 'negative' : 'positive' ?>">
                            <?= number_format($t['amount'], 2, ',', ' ') ?> kr
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Radera konto -->
    <section class="danger-zone">
        <h3>Radera mitt konto</h3>
        <p>Varning: Detta raderar alla dina konton och transaktioner permanent!</p>
        <form action="auth.php?action=delete" method="post"
              onsubmit="return confirm('Är du säker? Detta går inte att ångra!');">
            <button type="submit" class="btn-log">Radera mitt konto</button>
        </form>
    </section>
</main>


<script>
    // Tab-switching 
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
        });
    });

    // Auto-göm noticer
    document.addEventListener('DOMContentLoaded', function () {
        var notice = document.querySelector('.notice.success, .notice.error');
        if (notice) {
            setTimeout(function () {
                notice.classList.add('is-hidden');
            }, 1500);
        }
    });
</script>
</body>
</html>
