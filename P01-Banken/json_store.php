<?php
// Definiera sökvägar till JSON-filerna
define('USERS_JSON_PATH', 'users.json');
define('TRANSACTIONS_JSON_PATH', 'transactions.json');

// tidszon
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Europe/Stockholm');
}

// Läs JSON-fil som array; om filen saknas, returnera tom array
function js_read_array(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $contents = file_get_contents($path);
    if ($contents === false) return [];
    $data = json_decode($contents ?: '[]', true);
    return is_array($data) ? $data : [];
}

// Skriv en JSON-array till fil
function js_write_array(string $path, array $data): bool {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($path, $json) !== false;
}

// ladda/spara användare
function load_users(): array {
    return js_read_array(USERS_JSON_PATH);
}

function save_users(array $users): bool {
    return js_write_array(USERS_JSON_PATH, $users);
}

// ladda/spara transaktioner
function load_transactions(): array {
    return js_read_array(TRANSACTIONS_JSON_PATH);
}

function save_transactions(array $txs): bool {
    return js_write_array(TRANSACTIONS_JSON_PATH, $txs);
}

// Lägg till händelse i transactions.json (append)
function append_transaction(array $tx): bool {
    $txs = load_transactions();
    $txs[] = $tx;
    return save_transactions($txs);
}

// Nuvarande tid som sträng
function now_str(): string {
    return date('Y-m-d H:i:s');
}

// Beräkna saldon per konto för en användare genom att summera alla transaktioner
function compute_balances_for_user(string $username): array {
    // returns [accountName => balanceInt]
    $balances = [];
    foreach (load_transactions() as $t) {
        if (($t['username'] ?? null) !== $username) continue;
        $acc = $t['account'] ?? 'Huvudkonto';
        $amt = (int)($t['amount'] ?? 0);
        if (!isset($balances[$acc])) $balances[$acc] = 0;
        $balances[$acc] += $amt;
    }
    return $balances;
}
