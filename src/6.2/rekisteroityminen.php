<?php
session_start();
require_once 'db.php';

$nimi = $_POST['nimi'];
$osoite = $_POST['osoite'];
$liittymisPVM = $_POST['liittymisPVM'];
$syntymavuosi = $_POST['syntymavuosi'];
$_SESSION['kayttajatunnus'] = $_POST['kayttajatunnus'];
$SalasanaHash = password_hash($_POST['salasana'], PASSWORD_DEFAULT);

try {
  $sql = "INSERT INTO jasenet (Nimi, Osoite, LiittymisPVM, Syntymavuosi, Kayttajatunnus, SalasanaHash)
  VALUES (?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  echo "New record created successfully";
} catch(PDOException $e) {
  echo $sql . "<br>" . $e->getMessage();
}

$conn = null;
?>