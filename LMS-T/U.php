<?php

/*if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['name'])) {
        echo "You must enter a name.";
    } else {
        $name = htmlspecialchars($_POST['name']);
        echo "Your name is $name.";
    }
}*/


/*$namn = $_POST['name'];
echo "Your name is". strtoupper($namn);*/


if (isset($_POST['name'])) {
    $namn = $_POST['name'];
    echo "Your name is " . strtoupper($namn);
}


?>

<?php

$ant = ["ant", "cup", "mango", "rat"];
//echo implode($ant);
//echo "$ant";

//echo "<pre>";
//var_dump($ant);
//echo "</pre>";


?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

<form action="U.php" method="post">
    <label>
        <input type="text" name="name" required>
    </label>
    <button type="submit">Submit</button>
</form>

</body>
</html>