<?php
// Databasinställningar
define('DB_HOST', 'localhost');
define('DB_NAME', 'bnkn-db');
define('DB_PORT', '1889');
define('DB_USER', 'root');
define('DB_PASS', 'root');


//define('DB_USER', 'als050104ao');
//define('DB_NAME', 'als050104ao_bnkn-db');
//define('DB_PASS', 'gb0SMyjOkU8xkL');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
} catch (Exception $e) {
    $pdo_error = $e->getMessage();
};
// Tidszon
date_default_timezone_set('Europe/Stockholm');
?>