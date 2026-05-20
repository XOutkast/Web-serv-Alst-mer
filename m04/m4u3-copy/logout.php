<?php
/**
 * Syfte: Loggar ut användaren genom att rensa session och rememberme-cookie.
 */
session_start();
setcookie("rememberme", "", time() - 3600, "/"); // ta bort cookie
session_unset(); // unset sessionens variabler
session_destroy(); // förstör sessionsdata
if (function_exists('session_regenerate_id')) { @session_regenerate_id(true); } // nytt ID
header("Location: index.php"); // tillbaka till startsidan
exit;
