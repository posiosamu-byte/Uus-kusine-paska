<?php
 
$json = file_get_contents("opiskelija.json");
 
$opiskelija = json_decode($json, true);
 
echo "Nimi: " . $opiskelija["nimi"] . "<br>";
echo "Ikä: " . $opiskelija["ika"] . "<br>";
echo "Ala: " . $opiskelija["ala"];
 
?>