<!-- <?php

// BASIC STRING OPERATIONS
$i = "my name is that<br>";
$e = "my name is" . str_repeat(" ", 5) . "that<br>";

echo strlen($i) . "<br>";
echo strtolower($i);
echo strtoupper($i);
echo substr($i, 0, 10) . "<br>";
echo $e . "<br>";

// SIMPLE IF STATEMENT
$x = 5;
$y = 8;

if ($x > $y) {
    echo "$x is greater than $y";
} else {
    echo "$x is less than $y";
}
echo "<br><br>";

// ARRAY OUTPUT
$arr = [4, 6, 8, 10, 12, 14];

echo "Array with print_r():<br>";
print_r($arr);
echo "<br><br>";

echo "Array with implode(): " . implode(", ", $arr) . "<br><br>";

echo "Array with foreach:<br>";
foreach ($arr as $value) {
    echo $value . "<br>";
}
echo "<br>";

// DATE & TIME
echo "Today's Date: " . date("Y-m-d") . "<br>";
echo "Current Timestamp: " . time() . "<br><br>";

// FOREACH & ASSOCIATIVE ARRAYS
$arr = [10, 20, 30, 40, 50, 60];

echo "Simple foreach:<br>";
foreach ($arr as $val) {
    echo $val . " ";
}
echo "<br><br>";

$ages = [
        "Anjali" => 25,
        "Kriti" => 30,
        "Ayushi" => 22
];

echo "Associative array:<br>";
foreach ($ages as $name => $age) {
    echo "$name => $age<br>";
}
echo "<br>";

// CONSTANTS
define("PI_VALUE", 3.14);
echo "Defined Constant PI_VALUE: " . PI_VALUE . "<br>";

echo "PHP's pi() function: " . pi() . "<br><br>";

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body style="background-color: ;; color: black;">

<h2>Simple Form</h2>

<form action="" method="post">
    <label>N1:</label>
    <input type="number" name="x" required><br><br>

    <label>N2:</label>
    <input type="number" name="y" required><br><br>

    <label>N3:</label>
    <input type="number" name="z" required><br><br>

    <input type="submit" value="Submit">
</form>

<?php

// FORM PROCESSING
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $x = floatval($_POST["x"]);
    $y = floatval($_POST["y"]);
    $z = floatval($_POST["z"]);

    $total = abs($x + $y + $z);

    echo "<h3>Form Result:</h3>";
    echo "Absolute Sum: $total<br><br>";
}

// FRUITS ARRAY EXAMPLE
$fruits = [
        "1" => "Apple",
        "2" => "Banana",
        "3" => "Kiwi",
        "4" => "Orange"
];

echo "<h3>Fruit List:</h3>";
foreach ($fruits as $id => $fruit) {
    echo "The fruit with number $id is $fruit<br>";
}

echo "<br>";

// REVERSE LOOP
echo "<h3>Countdown from 10:</h3>";
for ($i = 10; $i >= 1; $i--) {
    echo $i . "<br>";
}
?>

</body>
</html> -->
