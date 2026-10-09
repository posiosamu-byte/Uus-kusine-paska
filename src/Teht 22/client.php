<?php
session_start();
if (!isset($_SESSION['rooli'])) {
    header("Location: kirjaudu.php");
    exit;
}
$rooli    = $_SESSION['rooli'];
$kayttaja = $_SESSION['kayttaja'];
$evaste   = session_name() . "=" . session_id();
session_write_close();   // pakollinen, muuten cURL-kutsu jumittuu istunnon lukitukseen
 
$api = "http://localhost/Teht%2022/api.php";
 
// cURL funktio: GET, POST, PUT ja DELETE
function api($metodi, $taulu, $id = null, $data = null, $haku = []) {
    global $api, $evaste;
    $url = "$api?taulu=$taulu" . ($id ? "&id=$id" : "");
    if ($haku) $url .= "&" . http_build_query($haku);
 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodi);
    curl_setopt($ch, CURLOPT_COOKIE, $evaste);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    }
    $vastaus = curl_exec($ch);
    curl_close($ch);
    return json_decode($vastaus, true);
}
 
// Taulut ja kenttien tyypit
$kentat = [
    "Asiakkaat" => ["etunimi" => "text", "sukunimi" => "text", "puhelin" => "tel",
                    "sahkoposti" => "email", "syntymaaika" => "date"],
    "Huoneet"   => ["huonenumero" => "number", "tyyppi" => "select", "kerros" => "number",
                    "hinta" => "number", "tila" => "number"],
    "Varaukset" => ["asiakas_id" => "number", "huone_id" => "number", "saapuminen" => "date",
                    "lahteminen" => "date", "henkilomaara" => "number", "tila" => "number",
                    "lisatiedot" => "text"]
];
 
// Hakuehdot
$haku = [];
foreach (["nimi", "tyyppi", "vapaat", "asiakas", "aktiiviset"] as $k) {
    if (!empty($_GET[$k])) $haku[$k] = $_GET[$k];
}
 
// Lomakkeen käsittely
$viesti = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($kentat[$_POST['taulu'] ?? ''])) {
    $taulu = $_POST['taulu'];
    $id    = $_POST['id'] ?? null;
    $data  = array_intersect_key($_POST, $kentat[$taulu]);
    $v     = null;
 
    if ($_POST['toiminto'] === 'lisaa')   $v = api('POST', $taulu, null, $data);
    if ($_POST['toiminto'] === 'muokkaa') $v = api('PUT', $taulu, $id, $data);
    if ($_POST['toiminto'] === 'poista')  $v = api('DELETE', $taulu, $id);
 
    $viesti = json_encode($v, JSON_UNESCAPED_UNICODE);
}
 
// Muokattava rivi (GET)
$muokattava = null;
$muokkaaTaulu = $_GET['muokkaa'] ?? '';
if (isset($kentat[$muokkaaTaulu]) && isset($_GET['id'])) {
    $muokattava = api('GET', $muokkaaTaulu, (int)$_GET['id']);
}
 
// Lisäys ja muokkauslomake
function lomake($taulu, $kentat, $rivi = null) {
    echo "<form method='post'>";
    echo "<input type='hidden' name='taulu' value='$taulu'>";
    if ($rivi) {
        echo "<input type='hidden' name='id' value='{$rivi['id']}'>";
        echo "<input type='hidden' name='toiminto' value='muokkaa'>";
    } else {
        echo "<input type='hidden' name='toiminto' value='lisaa'>";
    }
    foreach ($kentat as $nimi => $tyyppi) {
        $arvo = htmlspecialchars($rivi[$nimi] ?? '');
 
        if ($tyyppi === "select") {
            echo "$nimi: <select name='$nimi'>";
            foreach (["normi", "villa"] as $vaihtoehto) {
                $valittu = ($arvo === $vaihtoehto) ? "selected" : "";
                echo "<option value='$vaihtoehto' $valittu>$vaihtoehto</option>";
            }
            echo "</select><br>";
        } else {
            echo "$nimi: <input type='$tyyppi' name='$nimi' value='$arvo'><br>";
        }
    }
    echo "<button>" . ($rivi ? "Tallenna" : "Lisää") . "</button></form>";
}
 
// Hakulomake
function hakulomake($taulu) {
    echo "<form method='get'>";
    if ($taulu === "Asiakkaat") {
        $n = htmlspecialchars($_GET['nimi'] ?? '');
        echo "Nimi: <input type='text' name='nimi' value='$n'> ";
    }
    if ($taulu === "Huoneet") {
        $t = $_GET['tyyppi'] ?? '';
        echo "Tyyppi: <select name='tyyppi'><option value=''>kaikki</option>";
        foreach (["normi", "villa"] as $v) {
            echo "<option value='$v'" . ($t === $v ? " selected" : "") . ">$v</option>";
        }
        echo "</select> ";
        echo "<label><input type='checkbox' name='vapaat' value='1'" .
             (!empty($_GET['vapaat']) ? " checked" : "") . "> Vain vapaat</label> ";
    }
    if ($taulu === "Varaukset") {
        $a = htmlspecialchars($_GET['asiakas'] ?? '');
        echo "Asiakas: <input type='text' name='asiakas' value='$a'> ";
        echo "<label><input type='checkbox' name='aktiiviset' value='1'" .
             (!empty($_GET['aktiiviset']) ? " checked" : "") . "> Vain aktiiviset</label> ";
    }
    echo "<button>Hae</button> <a href='client.php'>Tyhjennä</a></form>";
}
 
// Lista
function lista($taulu, $kentat, $haku = []) {
    global $rooli;
    $rivit = api('GET', $taulu, null, null, $haku) ?? [];
    echo "<table border='1' cellpadding='4'><tr><th>id</th>";
    foreach ($kentat as $nimi => $t) echo "<th>$nimi</th>";
    echo "<th></th></tr>";
 
    foreach ($rivit as $r) {
        echo "<tr><td>{$r['id']}</td>";
        foreach ($kentat as $nimi => $t) echo "<td>" . htmlspecialchars($r[$nimi] ?? '') . "</td>";
        echo "<td><a href='?muokkaa=$taulu&id={$r['id']}'>Muokkaa</a>";
        if ($rooli === 'yllapitaja') {
            echo " <form method='post' style='display:inline'>
                    <input type='hidden' name='taulu' value='$taulu'>
                    <input type='hidden' name='id' value='{$r['id']}'>
                    <input type='hidden' name='toiminto' value='poista'>
                    <button>" . ($taulu === "Varaukset" ? "Peru" : "Poista") . "</button>
                  </form>";
        }
        echo "</td></tr>";
    }
    echo "</table>";
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <link rel="stylesheet" href="tyyli.css">
    <meta charset="utf-8">
    <title>Hotelli</title>
</head>
<body>
    <h1>Hotelli</h1>
    <p>Kirjautunut: <?= htmlspecialchars($kayttaja) ?> (<?= htmlspecialchars($rooli) ?>)
       | <a href="yhteenveto.php">Yhteenveto</a>
       | <a href="kirjaudu.php?ulos=1">Kirjaudu ulos</a></p>
    <?php if ($viesti) echo "<p><b>Vastaus:</b> " . htmlspecialchars($viesti) . "</p>"; ?>
 
    <?php foreach ($kentat as $taulu => $k): ?>
        <h2><?= $taulu ?></h2>
 
        <h3>Lisää</h3>
        <?php lomake($taulu, $k); ?>
 
        <?php if ($muokattava && $muokkaaTaulu === $taulu): ?>
            <h3>Muokkaa</h3>
            <?php lomake($taulu, $k, $muokattava); ?>
        <?php endif; ?>
 
        <h3>Lista</h3>
        <?php hakulomake($taulu); ?>
        <?php lista($taulu, $k, $haku); ?>
        <hr>
    <?php endforeach; ?>
</body>
</html>
 