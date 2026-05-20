<?php
echo "<link rel='stylesheet' href='style.css'>";

// Favicon
$favicon = "../../Landing_page/bilder/Favicon-A.jpg";
echo "<link rel='icon' type='image/jpg' href='$favicon'>";

$fav = "Favicon-a.jpg";
echo "<link rel='icon' type='image/jpg' href='$fav'>";


echo "<h4>mom2-2</h4>"; /*Echo typ samma son print (py)*/
echo "<h3>Jag jobbar med PHP!</h3>";
echo "<p>Hej, jag heter X, och jag lär mig PHP!</p>";

// Mom2-2: Namn och kurs
$namn = "Johan"; // Elevens namn
$kurs = "Webbserverprogrammering 1"; // Kursnamn

echo "$namn läser kursen $kurs.<br>"; // Visar namn och kurs

// Mom2-2: Beräkning av cirkelns area och omkrets
$radie = 4; // Cirkelns radie
$pi = 3.14; // Värdet av pi (alltså en variable)

$omkrets = 2 * $pi * $radie; // Beräknar omkrets
$area = $pi * $radie * $radie; // Beräknar area

echo "Om radien är $radie så är omkretsen $omkrets och arean $area."; // Skriver ut resultat

// Mom2-3: Alternativa sätt att beräkna och skriva ut
$omkrets = 2 * $pi * $radie;
$area = $pi * $radie * $radie;

// Fördel med variabler: lättare att läsa och felsöka
echo "Om radien är $radie så är omkretsen $omkrets och arean $area.<br>";

// Mom2-4: Hantering av citattecken och radbrytningar
echo "Jag tycker det är \"kul\" med PHP!\nNej, jag skojade bara! <br>";
echo "<br>";
echo "Jag tycker det är \"kul\" med PHP!<br>\nNej, jag skojade bara!";

// Mom2-5: Strängmanipulation
$str = "WebbServerProgrammering 1";

// Längd på strängen
echo strlen($str);
echo "<br>";

// Bara gemener
echo strtolower($str);
echo "<br>";

// Bara versaler
echo strtoupper($str);
echo "<br>";

// Reverse + första bokstaven stor
echo ucfirst(strtolower(strrev($str)));
echo "<br>";

// Svenska tecken å, ä, ö
$str2 = "Programmering är roligt äöäöåäöåäöåäöö";
echo mb_strlen($str2); // Korrekt längd
echo "<br>";
echo mb_strtolower($str2); // Bara gemener
echo "<br>";
echo mb_strtoupper($str2); // Bara versaler
echo "<br>";
echo mb_convert_case($str2, MB_CASE_TITLE, "UTF-8"); // Varje ord börjar med stor bokstav
echo "<br>";

// Mom2-6: Arrayer och utskrift
$namn = array("AAA", "BBB", "CCC");
$mail = array("info@AAA.se", "BBB@mail.se", "CCC@test.se");

echo "<pre>";
echo "Namn-array:\n";
var_export($namn);
echo "\n\nMail-array:\n";
var_export($mail);
echo "</pre>";

echo "<p>$namn[0] har mailadressen $mail[0]</p>";
echo "<p>$namn[1] har mailadressen $mail[1]</p>";
echo "<p>$namn[2] har mailadressen $mail[2]</p>";

// Mom2-8: If-satser och switch
$timme = date("H");
echo "Klockan är: $timme<br>";

if ($timme > 16) { echo "Skoldagen är slut!"; } // Enkel if

if ($timme >= 8 && $timme <= 16) { echo "Det är skoldag!"; } else { echo "Det är inte skoldag!"; } // If-else

if ($timme < 8) { echo "Skoldagen har inte börjat."; } else if ($timme > 16) { echo "Skoldagen är slut."; } else { echo "Skoldagen pågår."; } // If-else if-else

$timmeInt = (int)$timme; // Används i switch
switch (true) {
    case ($timmeInt < 8): echo "Skoldagen har inte börjat."; break;
    case ($timmeInt >= 8 && $timmeInt <= 16): echo "Skoldagen pågår."; break;
    case ($timmeInt > 16): echo "Skoldagen är slut."; break;
    default: echo "Felaktig tid.";
}

// Mom2-9: Logiska operatorer
if ($timme >= 8 and $timme <= 16) { echo "Det är skoldag! (sätt 1)<br>"; }
if ($timme >= 8 && $timme <= 16) { echo "Det är skoldag! (sätt 2)<br>"; }
if (!($timme < 8 or $timme > 16)) { echo "Det är skoldag! (sätt 3)<br>"; }
if (($timme >= 8 xor $timme > 16) == false) { echo "Det är skoldag! (sätt 4)<br>"; }

// Mom2-10: Loopar och iteration
for ($i = 0; $i < 5; $i++) { echo "$i<br>"; } // For-loop

$i = 0; while ($i < 5) { echo "$i<br>"; $i++; } // While-loop

$i = 0; do { echo "$i<br>"; $i++; } while ($i < 5); // Do-while-loop

$array = array(3,3,6,5,1);
foreach ($array as $a) { echo "$a<br>"; } // Foreach-loop

$riktnr = array("031"=>"Göteborg","040"=>"Malmö","07XX"=>"Mobil","08"=>"Stockholm");
foreach ($riktnr as $nr=>$ort) { echo "<p>$ort har riktnr $nr.</p>"; } // Foreach med nyckel

for ($i=1;$i<=20;$i++){
    if($i % 3 == 0){ continue; } // Hoppar över tal delbara med 3
    if($i >= 15){ break; } // Avbryt vid 15
    echo "$i<br>";
}

// Mom2-11: Funktioner
include('functions.php'); // JAg inkluderar filen (functions.php) så jag kan använda dem här

// Skriver ut namn och klass för olika elever med print_student_info
print_student_info("Anders","Andersson","1A"); // Skriver ut Anders Andersson och klass 1A
print_student_info("Maria","Johansson","2B");  // Samma här

// Beräknar och skriver ut area och omkrets för cirklar med olika radier
cirkel_info(3); // Cirkel med radie 3 –
cirkel_info(5); // Cirkel med radie 5 –

// Exempel på hur man använder funktioner som returnerar värden
$radie1=3;
$radie2=5;

// Beräknar den total arean och totala omkrets för två cirklar
$total_area = cirkel_area($radie1) + cirkel_area($radie2);
$total_omkrets = cirkel_omkrets($radie1) + cirkel_omkrets($radie2);

// Skriver ut resultaten med avrundning till två decimaler
echo "Den totala arean på cirklar med radierna $radie1 och $radie2 är " . round($total_area,2) . "<br>";
echo "Den totala omkretsen på cirklar med radierna $radie1 och $radie2 är " . round($total_omkrets,2) . "<br>";

// Mom2-12: Tabell med färgade några förjade rader
echo "<div style='border: 2px solid rgba(0,0,0,0.44); padding: 0px; display: inline-block;'>";
echo "<table cellpadding='1' cellspacing='0' style='border-collapse: separate; border-spacing: 2px; width: 800px;'>";

// Loopar från 1 till 20
for($i=1;$i<=20;$i++){
    $row_color = ""; // Standardfärg/defaukt för raden

    // Färgarna visar rader enligt det: gul för udda och jämna tall 3,6,12...., röd för vissa siffror, orange för 15
    if(in_array($i,[3,6,9,12,18])){ $row_color="#FFFF00"; } // Gul
    elseif(in_array($i,[5,10,20])){ $row_color="#FF0000"; } // Röd
    elseif($i==15){ $row_color="#FFA500"; } // Orange

    $row_height="height:24px;"; // Standard/default höjd för raderna

    echo "<tr style='$row_height'>";

    // Kolumn 1: visar siffran med vänsterjustering och gränser
    echo "<td style='width:75%; text-align:left; padding-left:10px; border-top:2px solid black; border-right:1px solid rgba(0,0,0,0.3); border-left:2px solid black; border-bottom:1px solid rgba(0,0,0,0.3); background-color:$row_color;'>$i</td>";

    // Kolumn 2: sätter en stjärna för udda tal, tom annars
    if($i%2==1){
        echo "<td style='width:50%; text-align:left; padding-left:10px; border-top:2px solid black; border-right:1px solid rgba(0,0,0,0.3); border-left:2px solid black; border-bottom:1px solid rgba(0,0,0,0.3); background-color:$row_color;'>*</td>";
    } else {
        echo "<td style='width:50%; text-align:left; padding-left:10px; border-top:2px solid black; border-right:1px solid rgba(0,0,0,0.3); border-left:2px solid black; border-bottom:1px solid rgba(0,0,0,0.3); background-color:$row_color;'></td>";
    }

    echo "</tr>";
}

echo "</table></div>";

?>
