<?php
session_start();
require 'tietokanta.php';
 
// Uloskirjautuminen
if (isset($_GET['ulos'])) {
    session_destroy();
    header("Location: kirjaudu.php");
    exit;
}
 
$virhe = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = $conn->prepare("SELECT * FROM Kayttajat WHERE kayttajanimi = ?");
    $s->execute([$_POST['kayttajanimi'] ?? '']);
    $k = $s->fetch(PDO::FETCH_ASSOC);
 
    if ($k && hash('sha256', $_POST['salasana'] ?? '') === $k['salasana']) {
        session_regenerate_id(true);
        $_SESSION['kayttaja'] = $k['kayttajanimi'];
        $_SESSION['rooli']    = $k['rooli'];
        header("Location: client.php");
        exit;
    }
    $virhe = "Väärä käyttäjänimi tai salasana";
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <link rel="stylesheet" href="tyyli.css">
    <meta charset="utf-8">
    <title>Kirjaudu</title>
</head>
<body>
    <h1>Kirjaudu</h1>
    <?php if ($virhe) echo "<p style='color:red'>$virhe</p>"; ?>
    <form method="post">
        Käyttäjänimi: <input type="text" name="kayttajanimi"><br>
        Salasana: <input type="password" name="salasana"><br>
        <button>Kirjaudu</button>
    </form>
</body>
</html>
 