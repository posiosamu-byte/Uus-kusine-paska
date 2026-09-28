<?php
 
header("Content-Type: application/json");
 
$opiskelija = [
"nimi" => "Maija",
"ika" => 17,
"ala" => "Tieto- ja viestintätekniikka",
"kaupunki" => "Kuopio",
"sahkoposti":"maija@example.com",
"puhelin":"0401234567"
];
 
echo json_encode($opiskelija);
 
?>