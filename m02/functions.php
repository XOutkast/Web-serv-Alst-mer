<?php
/*
 * functions.php
 * Här samlas jag alla funktioner från moment 2.11
*/

/*
 * print_student_info
 * Skriver ut namn och klass
*/
function print_student_info($fornamn, $efternamn, $klass){
    // Kombination förnamn, efternamn och klass och sen skriver ut
    echo "Jag heter $fornamn $efternamn och jag går i klass $klass.<br>";
}

/*
 * cirkel_info
 * Beräknar och skriver ut area och omkrets för en cirkel
*/
function cirkel_info($radie){
    // Beräkna area
    $area = M_PI * $radie * $radie;
    // Beräkna omkrets
    $omkrets = 2 * M_PI * $radie;
    // Skriv ut radie-info
    echo "För en cirkel med radie $radie:<br>";
    // Skriv ut area (avrundad till 2 decimaler)
    echo "Area: " . round($area, 2) . "<br>";
    // Skriv ut omkrets (avrundad till 2 decimaler)
    echo "Omkrets: " . round($omkrets, 2) . "<br><br>";
}

/*
 * cirkel_area
 * Returnerar arean för en cirkel
*/
function cirkel_area($radie){
    // Returnera beräknad area
    return M_PI * $radie * $radie;
}

/*
 * cirkel_omkrets
 * Returnerar omkretsen för en cirkel
*/
function cirkel_omkrets($radie){
    // Returnera beräknad omkrets
    return 2 * M_PI * $radie;
}
?>
