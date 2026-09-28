<?php
 
header("Content-Type: application/json");
 
$opiskelijat = [
["id" => 1, "nimi" => "Matti", "ala" => "Ohjelmistokehitys"],
["id" => 2, "nimi" => "Maija", "ala" => "Tietoverkot"],
["id" => 3, "nimi" => "Pekka", "ala" => "Kyberturvallisuus"]
];
 
$id = $_GET["id"];
 
foreach ($opiskelijat as $opiskelija) {
if ($opiskelija["id"] == $id) {
echo json_encode($opiskelija);
}
}
 
?>