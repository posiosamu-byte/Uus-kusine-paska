<?php
session_start();
require_once 'db.php';

$_SESSION['kayttajatunnus'] = $_POST['kayttajatunnus'];
$SalasanaHash = password_hash($_POST['salasana'], PASSWORD_DEFAULT);

try {
  $sql = "INSERT INTO jasenet (Kayttajatunnus, SalasanaHash)
  VALUES (?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  echo "New record created successfully";
} catch(PDOException $e) {
  echo $sql . "<br>" . $e->getMessage();
}

  header("Location: vuokraus.html");
  exit;

$conn = null;
?>