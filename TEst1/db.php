<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "test2";

// Check if mysqli extension is available
if (!class_exists('mysqli')) {
    error_log("WARNING: mysqli extension not enabled. Database features disabled.");
    $conn = null;
} else {
    $conn = @new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        $conn = null;
    } else {
        $conn->set_charset("utf8");
        
        // Verify connection
        $test = $conn->query("SELECT DATABASE() AS dbname");
        if ($test) {
            $row = $test->fetch_assoc();
            // Database connected successfully
        }
    }
}


?>
