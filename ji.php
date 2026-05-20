<?php
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $celsius = $_POST['celsius'];
    if (is_numeric($celsius)) {
        $fahrenheit = ($celsius * 9/5) + 32;
        echo "<p>$celsius °C is equal to $fahrenheit °F</p>";
    } else {
        echo "<p>Please enter a valid number!</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Celsius to Fahrenheit Converter</title>
</head>
<body>
<h2>Celsius to Fahrenheit Conversion</h2>
<form method="post">
    <label for="celsius">Enter Celsius:</label>
    <input type="text" name="celsius" required>
    <button type="submit">Convert</button>
</form>
</body>
</html>
