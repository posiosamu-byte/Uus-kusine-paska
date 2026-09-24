<?php
require_once 'db.php';

$elokuvaID = $_POST['elokuvaID'];
$palautusPVM = $_POST['palautusPVM'];
$vuokrausPVM = $_POST['vuokrausPVM'];

try {
  $sql = "INSERT INTO jasenet (ElokuvaID, PalautusPVM, VuokrausPVM)
  VALUES (?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  echo "New record created successfully";
} catch(PDOException $e) {
  echo $sql . "<br>" . $e->getMessage();
}

$conn = null;
?>