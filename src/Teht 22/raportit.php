<?php

session_start();
if (!isset($_SESSION['rooli'])) {
    header("Location: kirjaudu.php");
    exit;
}
$evaste = session_name() . "=" . session_id();
session_write_close();
$api = "http://localhost/Teht%2022/api.php";

function api($taulu) {
    global $api, $evaste;
    $ch = curl_init("$api?taulu=$taulu");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $evaste);
    $vastaus = curl_exec($ch);
    curl_close($ch);
    return json_decode($vastaus, true) ?? [];
}

// Fetch everything through the API
$asiakkaat = api("Asiakkaat");
$huoneet   = api("Huoneet");
$varaukset = api("Varaukset");

// Index by id so we can look things up quickly
$asiakasId = array_column($asiakkaat, null, 'id');
$huoneId   = array_column($huoneet, null, 'id');

// Statistics
$asiakkaitaYht = count($asiakkaat);
$huoneitaYht   = count($huoneet);

$aktiivisia = 0;
$vapaita    = 0;
$liikevaihto = 0;
$kuukausi = date('Y-m');   // e.g. 2026-10

foreach ($huoneet as $h) {
    if ($h['tila'] == 1) $vapaita++;
}

foreach ($varaukset as $v) {
    if ($v['tila'] != 1) continue;   // skip cancelled
    $aktiivisia++;

    // Revenue: active reservations that arrive this month
    if (substr($v['saapuminen'], 0, 7) === $kuukausi) {
        $h = $huoneId[$v['huone_id']] ?? null;
        if ($h) {
            $yot = (strtotime($v['lahteminen']) - strtotime($v['saapuminen'])) / 86400;
            $liikevaihto += $yot * $h['hinta'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <link rel="stylesheet" href="tyyli.css">
    <meta charset="utf-8">
    <title>Raportit</title>
</head>
<body>
    <h1>Raportit</h1>
<ul>
    <li>Asiakkaita: <?= $asiakkaitaYht ?></li>
    <li>Huoneita: <?= $huoneitaYht ?></li>
    <li>Aktiivisia varauksia: <?= $aktiivisia ?></li>
    <li>Vapaita huoneita: <?= $vapaita ?></li>
    <li>Tämän kuukauden liikevaihto: <?= number_format($liikevaihto, 2, ',', ' ') ?> €</li>
</ul>
</body>
</html>
