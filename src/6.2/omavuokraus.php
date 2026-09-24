<?php
session_start();
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

<!DOCTYPE html>
<html>
<head>
    <title>Vuokraus</title>
</head>
<body>
    <h1>Omat Vuokraukset</h1>
    <a href="logout.php">Kirjaudu ulos</a>
    
<form action="omavuokraus.php" method="post">
    <label for="elokuvaID">Elokuva ID:</label>
    <label for="palautusPVM">Palautus PVM:</label>
    <label for="vuokrausPVM">Vuokraus PVM:</label>
    <button type="submit">Poista</button>
</form>
</body>

<form action="omavuokraus.php" method="post">
    <label for="elokuvaID">Elokuva:</label>
    <input type="dropdown" id="elokuvaID" name="elokuvaID">
    <select>
        <option value="">Valitse elokuva</option>
        <option value="1">Elokuva 1</option>
        <option value="2">Elokuva 2</option>
        <option value="3">Elokuva 3</option>
    </select><br><br>
    <label for="palautusPVM">Palautus PVM:</label>
    <input type="date" id="palautusPVM" name="palautusPVM">
    <label for="vuokrausPVM">Vuokraus PVM:</label>
    <input type="date" id="vuokrausPVM" name="vuokrausPVM" required>
    <button type="submit">Vuokraa elokuva</button>

</form>
<body>