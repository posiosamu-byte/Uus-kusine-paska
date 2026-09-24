<?php
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

<!DOCTYPE html>
<html>
<head>
    <title>Kirjaudu Sisään</title>
</head>
<body>
    <h1>Kirjaudu Sisään</h1>
    <form action="kirjautuminen.php" method="post">
        <label for="Kayttajatunnus">Käyttäjätunnus:</label>
        <input type="text" id="Kayttajatunnus" name="Kayttajatunnus"><br><br>
        <label for="Salasana">Salasana:</label>
        <input type="password" id="Salasana" name="Salasana"><br><br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>