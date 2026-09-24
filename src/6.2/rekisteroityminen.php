<?php
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

<!DOCTYPE html>
<html>
<head>
    <title>Rekisteröityminen</title>
</head>
<body>
    <h1>Rekisteröityminen</h1>
    <form action="rekisteroityminen.php" method="post">
        <label for="nimi">Nimi:</label>
        <input type="text" id="nimi" name="nimi" required><br><br>

        <label for="osoite">Osoite:</label>
        <input type="text" id="osoite" name="osoite" required><br><br>

        <label for="liittymisPVM">LiittymisPVM:</label>
        <input type="date" id="liittymisPVM" name="liittymisPVM" required><br><br>

        <label for="syntymavuosi">Syntymavuosi:</label>
        <input type="number" id="syntymavuosi" name="syntymavuosi" required><br><br>

        <label for="kayttajatunnus">Käyttäjätunnus:</label>
        <input type="text" id="kayttajatunnus" name="kayttajatunnus" required><br><br>

        <label for="salasana">Salasana:</label>
        <input type="password" id="salasana" name="salasana" required><br><br>

        <input type="submit" value="Rekisteröidy">
    </form>
    <a href="kirjautuminen.php">Kirjaudu sisään</a>
</body>