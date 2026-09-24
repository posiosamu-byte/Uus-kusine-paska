<?php
$servername = "db";
$username = "root";
$password = "root";
$dbname = "Elokuvavuokraus";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
  die("Could not connect. " . $e->getMessage());
}

$conn = null;
?>