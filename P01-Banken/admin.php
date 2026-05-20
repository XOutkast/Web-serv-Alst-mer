<?php
// sätta en tidszon
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Europe/Stockholm');
}
// inkludera de filer
require "check_login.php";
require_once 'json_store.php';

// Hämta användarens namn från session
$username = $_SESSION['username'];

// Beräkna saldo från händelser (event sourcing)
$balances = compute_balances_for_user($username);

// Skapa lista med andra användare för överföringar
$allUsers = load_users();
$otherUsernames = [];
foreach ($allUsers as $u) {
    if (!empty($u['username']) && $u['username'] !== $username) {
        $otherUsernames[] = $u['username'];
    }
}

// Hämta transaktioner för historik
$allTx = load_transactions();
$userTx = [];
foreach ($allTx as $t) {
    if (($t['username'] ?? null) === $username) {
        $userTx[] = $t;
    }
}

// Sortera transaktioner (senaste först) och begränsa till 500
usort($userTx, function ($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});
$transactions = array_slice($userTx, 0, 500);

// Skapa kontolista från beräknade saldon
$accounts = [];
$totalBalance = 0;
foreach ($balances as $accountName => $balance) {
    $accounts[] = [
        'account_name' => $accountName,
        'balance' => $balance,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $totalBalance += $balance;
}
function type_label_sv($type)
{
    switch ($type) {
        case 'deposit':
            return 'Insättning';
        case 'withdraw':
            return 'Uttag';
        case 'transfer-in':
            return 'Överföring in';
        case 'transfer-out':
            return 'Överföring ut';
        case 'account-open':
            return 'Öppnat konto';
        default:
            return $type ?? '';
    }
}

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

<!-- MEDDELANDE - notiser -->
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
    <!-- Kontoöversikt -->
    <section class="accounts-section">
        <h2>Mina Konton</h2>
        <div class="accounts-grid" id="accountsGrid">
            <?php foreach ($accounts as $account): ?>
                <div class="account-card" data-account="<?= htmlspecialchars($account['account_name']) ?>">
                    <h3><?= htmlspecialchars($account['account_name']) ?></h3>
                    <p class="balance"><?= $account['balance'] ?> kr</p>
                    <small>Skapat: <?= htmlspecialchars(substr($account['created_at'], 0, 10)) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="total-balance">
            <strong>Totalt saldo:</strong> <?= $totalBalance ?> kr
        </div>
    </section>

    <!-- Transaktionsformulär -->
    <section class="actions-section">
        <h2>Hantera Transaktioner</h2>
        <div class="form-tabs">
            <button class="tab-btn active" data-tab="deposit" type="button">Insättning</button>
            <button class="tab-btn" data-tab="withdraw" type="button">Uttag</button>
            <button class="tab-btn" data-tab="transfer" type="button">Överföring</button>
            <button class="tab-btn" data-tab="open-account" type="button">Nytt Konto</button>
        </div>

        <!-- Insättning -->
        <form action="actions.php?action=deposit" method="post" class="tab-content active" id="deposit">
            <h3>Insättning</h3>
            <label for="deposit_account">Till konto:</label>
            <select name="account" id="deposit_account" required>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['account_name']) ?>">
                        <?= htmlspecialchars($acc['account_name']) ?> (<?= $acc['balance'] ?> kr)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="amount" placeholder="Belopp" step="1" min="1" required>
            <button type="submit">Sätt in</button>
        </form>

        <!-- Uttag -->
        <form action="actions.php?action=withdraw" method="post" class="tab-content" id="withdraw">
            <h3>Uttag</h3>
            <label for="withdraw_account">Från konto:</label>
            <select name="account" id="withdraw_account" required>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['account_name']) ?>">
                        <?= htmlspecialchars($acc['account_name']) ?> (<?= $acc['balance'] ?> kr)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="amount" placeholder="Belopp" step="1" min="1" required>
            <button type="submit">Ta ut</button>
        </form>

        <!-- Överföring -->
        <form action="actions.php?action=transfer" method="post" class="tab-content" id="transfer">
            <h3>Överföring</h3>
            <label for="from_account">Från konto:</label>
            <select name="from_account" id="from_account" required>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['account_name']) ?>">
                        <?= htmlspecialchars($acc['account_name']) ?> (<?= $acc['balance'] ?> kr)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="to_user_input">Till användare:</label>
            <input list="usersList" type="text" name="to_user" id="to_user_input"
                   placeholder="Mottagarens användarnamn" required autocomplete="off">
            <datalist id="usersList">
                <option value="<?= htmlspecialchars($username) ?>"></option>
                <?php foreach ($otherUsernames as $u): ?>
                    <option value="<?= htmlspecialchars($u) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            
            <div id="to_account_wrapper" style="display:none;">
                <label for="to_account">Till konto:</label>
                <select name="to_account" id="to_account">
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= htmlspecialchars($acc['account_name']) ?>">
                            <?= htmlspecialchars($acc['account_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <input type="number" name="amount" placeholder="Belopp" step="1" min="1" required>
            <button type="submit">Överför</button>
        </form>
        
        <!-- Öppna nytt konto -->
        <form action="actions.php?action=open-account" method="post" class="tab-content" id="open-account">
            <h3>Öppna nytt konto</h3>
            <input type="text" name="account_name" placeholder="Kontonamn" required>
            <button type="submit">Skapa konto</button>
        </form>
    </section>

    <!-- Transaktionshistorik -->
    <section class="transactions-section">
        <h2>Transaktionshistorik</h2>
        <!-- Server-side filterformulär -->
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
                <?php
                // Filtrering av transaktioner
                $filteredTransactions = $transactions;
                if (!empty($_GET['account'])) {
                    $filteredTransactions = array_filter($filteredTransactions, function ($t) {
                        return ($t['account'] ?? 'Huvudkonto') === $_GET['account'];
                    });
                }
                // Om en typ är vald i URL, filtrera transaktionerna så att endast de av vald typ visas
                if (!empty($_GET['type'])) {
                    $filteredTransactions = array_filter($filteredTransactions, function ($t) {
                        return ($t['type'] ?? '') === $_GET['type'];
                    });
                }
                // Loopa igenom filtrerade transaktioner och skapa en tabellrad för varje,
                // med datum, konto, typ och belopp.
                foreach ($filteredTransactions as $t): ?>
                    <tr data-account="<?= htmlspecialchars($t['account'] ?? 'Huvudkonto') ?>"
                        data-type="<?= htmlspecialchars($t['type'] ?? '') ?>">
                        <td><?= htmlspecialchars(substr($t['date'] ?? '', 0, 16)) ?></td>
                        <td><?= htmlspecialchars($t['account'] ?? 'Huvudkonto') ?></td>
                        <td><?= htmlspecialchars(type_label_sv($t['type'] ?? '')) ?></td>
                        <td class="<?= (isset($t['amount']) && $t['amount'] < 0) ? 'negative' : 'positive' ?>">
                            <?= (int)($t['amount'] ?? 0) ?> kr
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
        <p>Detta raderar alla dina konton och transaktioner permanent! <br> och det går inte att få den tillbaka</p>
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

    // Visa "till konto" endast när användaren överför till sig själv
    const toUserInput = document.getElementById('to_user_input');
    const toAccountWrapper = document.getElementById('to_account_wrapper');
    const currentUsername = "<?= htmlspecialchars($username) ?>";
    
    toUserInput.addEventListener('input', function() {
        if (this.value === currentUsername) {
            toAccountWrapper.style.display = 'block';
            document.getElementById('to_account').setAttribute('required', 'required');
        } else {
            toAccountWrapper.style.display = 'none';
            document.getElementById('to_account').removeAttribute('required');
        }
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
