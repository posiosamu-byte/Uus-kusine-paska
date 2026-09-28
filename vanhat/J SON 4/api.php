<?php
 
header("Content-Type: application/json");
 
$nimi = $_GET["nimi"];
$ika = $_GET["ika"];
 
echo json_encode([
"nimi" => $nimi,
"ika" => $ika
]);
 
?>