<?php
header("Content-Type: application/json; charset=utf-8");
require 'tietokanta.php';
 
// Kirjautumisen ja roolin tarkistus
session_start();
$rooli = $_SESSION['rooli'] ?? null;
session_write_close();
 
if (!$rooli) {
    http_response_code(401);
    echo json_encode(["virhe" => "Kirjaudu sisään"]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $rooli !== 'yllapitaja') {
    http_response_code(403);
    echo json_encode(["virhe" => "Vain ylläpitäjä voi poistaa"]);
    exit;
}
 
// Apufunktiot
function vastaa($koodi, $data) {
    http_response_code($koodi);
    echo json_encode($data);
    exit;
}
function luku($v, $min = 0, $max = null) {
    $o = filter_var($v, FILTER_VALIDATE_INT);
    return $o !== false && $o >= $min && ($max === null || $o <= $max);
}
function teksti($v) {
    return is_string($v) && trim($v) !== '' && mb_strlen($v) <= 255;
}
function paiva($v) {
    $d = is_string($v) ? DateTime::createFromFormat('Y-m-d', $v) : false;
    return $d && $d->format('Y-m-d') === $v;
}
 
// Taulut ja tarkistussäännöt
$saannot = [
    "Huoneet" => [
        "huonenumero" => fn($v) => luku($v, 1),
        "tyyppi"      => fn($v) => in_array($v, ["normi", "villa"], true),
        "kerros"      => fn($v) => luku($v, 0),
        "hinta"       => fn($v) => luku($v, 1),
        "tila"        => fn($v) => luku($v, 0, 1)      // 1 = vapaa, 0 = ei käytössä
    ],
    "Asiakkaat" => [
        "etunimi"     => fn($v) => teksti($v),
        "sukunimi"    => fn($v) => teksti($v),
        "puhelin"     => fn($v) => is_string($v) && preg_match('/^\+?[0-9]{7,15}$/', $v),
        "sahkoposti"  => fn($v) => teksti($v) && filter_var($v, FILTER_VALIDATE_EMAIL),
        "syntymaaika" => fn($v) => paiva($v) && $v <= date('Y-m-d')
    ],
    "Varaukset" => [
        "asiakas_id"   => fn($v) => luku($v, 1),
        "huone_id"     => fn($v) => luku($v, 1),
        "saapuminen"   => fn($v) => paiva($v),
        "lahteminen"   => fn($v) => paiva($v),
        "henkilomaara" => fn($v) => luku($v, 1, 10),
        "tila"         => fn($v) => luku($v, 0, 1),    // 1 = voimassa, 0 = peruttu
        "lisatiedot"   => fn($v) => $v === null || (is_string($v) && mb_strlen($v) <= 255)
    ]
];
$valinnaiset = ["lisatiedot"];
 
$metodi = $_SERVER['REQUEST_METHOD'];
$taulu  = $_GET['taulu'] ?? '';
$id     = $_GET['id'] ?? null;
$data   = json_decode(file_get_contents("php://input"), true) ?? [];
 
if (!isset($saannot[$taulu])) {
    vastaa(400, ["virhe" => "Tuntematon taulu"]);
}
if ($id !== null && !luku($id, 1)) {
    vastaa(400, ["virhe" => "Virheellinen id"]);
}
 
// Vain sallitut sarakkeet
$data = array_intersect_key($data, $saannot[$taulu]);
 
// Tarkistus
function tarkista($conn, $taulu, $data, $saannot, $valinnaiset, $vanha = null) {
    $virheet = [];
 
    // POST: pakolliset kentät
    if ($vanha === null) {
        foreach ($saannot[$taulu] as $kentta => $s) {
            if (!array_key_exists($kentta, $data) && !in_array($kentta, $valinnaiset)) {
                $virheet[] = "$kentta puuttuu";
            }
        }
    }
    // Annettujen kenttien muoto
    foreach ($data as $kentta => $arvo) {
        if (!$saannot[$taulu][$kentta]($arvo)) {
            $virheet[] = "$kentta on virheellinen";
        }
    }
    if ($virheet) return $virheet;
 
    // Yhdistetään vanhat ja uudet arvot vertailuja varten
    $yht = array_merge($vanha ?? [], $data);
    $id  = $vanha['id'] ?? 0;
 
    if ($taulu === "Huoneet" && isset($data['huonenumero'])) {
        $s = $conn->prepare("SELECT id FROM Huoneet WHERE huonenumero = ? AND id != ?");
        $s->execute([$data['huonenumero'], $id]);
        if ($s->fetch()) $virheet[] = "huonenumero on jo käytössä";
    }
 
    if ($taulu === "Varaukset") {
        if ($yht['lahteminen'] <= $yht['saapuminen']) {
            $virheet[] = "lahteminen pitää olla saapumisen jälkeen";
        }
        if (isset($data['asiakas_id'])) {
            $s = $conn->prepare("SELECT id FROM Asiakkaat WHERE id = ?");
            $s->execute([$data['asiakas_id']]);
            if (!$s->fetch()) $virheet[] = "asiakasta ei löydy";
        }
        if (isset($data['huone_id'])) {
            $s = $conn->prepare("SELECT id FROM Huoneet WHERE id = ?");
            $s->execute([$data['huone_id']]);
            if (!$s->fetch()) $virheet[] = "huonetta ei löydy";
        }
    }
    return $virheet;
}
 
// Päälogiikka
try {
    switch ($metodi) {
 
        case 'GET':   // haku
            if ($id) {
                $s = $conn->prepare("SELECT * FROM $taulu WHERE id = ?");
                $s->execute([$id]);
                $rivi = $s->fetch(PDO::FETCH_ASSOC);
                if (!$rivi) vastaa(404, ["virhe" => "Ei löytynyt"]);
                vastaa(200, $rivi);
            }
 
            // listaus hakuehdoilla
            $sql   = "SELECT * FROM $taulu";
            $ehdot = [];
            $arvot = [];
 
            if ($taulu === "Asiakkaat" && !empty($_GET['nimi'])) {
                $ehdot[] = "CONCAT(etunimi, ' ', sukunimi) LIKE ?";
                $arvot[] = "%" . $_GET['nimi'] . "%";
            }
            if ($taulu === "Huoneet") {
                if (!empty($_GET['tyyppi'])) {
                    $ehdot[] = "tyyppi = ?";
                    $arvot[] = $_GET['tyyppi'];
                }
                if (!empty($_GET['vapaat'])) {
                    $ehdot[] = "tila = 1";
                }
            }
            if ($taulu === "Varaukset") {
                $sql = "SELECT Varaukset.* FROM Varaukset
                        LEFT JOIN Asiakkaat ON Asiakkaat.id = Varaukset.asiakas_id";
                if (!empty($_GET['asiakas'])) {
                    $ehdot[] = "CONCAT(Asiakkaat.etunimi, ' ', Asiakkaat.sukunimi) LIKE ?";
                    $arvot[] = "%" . $_GET['asiakas'] . "%";
                }
                if (!empty($_GET['aktiiviset'])) {
                    $ehdot[] = "Varaukset.tila = 1";
                }
            }
            if ($ehdot) $sql .= " WHERE " . implode(" AND ", $ehdot);
 
            $s = $conn->prepare($sql);
            $s->execute($arvot);
            vastaa(200, $s->fetchAll(PDO::FETCH_ASSOC));
            break;
 
        case 'POST':  // lisäys
            if (!$data) vastaa(400, ["virhe" => "Ei tietoja"]);
            if (!isset($data['tila']) && $taulu === "Varaukset") $data['tila'] = 1;
            $virheet = tarkista($conn, $taulu, $data, $saannot, $valinnaiset);
            if ($virheet) vastaa(422, ["virheet" => $virheet]);
 
            $sarakkeet = implode(",", array_keys($data));
            $paikat    = implode(",", array_fill(0, count($data), "?"));
            $s = $conn->prepare("INSERT INTO $taulu ($sarakkeet) VALUES ($paikat)");
            $s->execute(array_values($data));
            vastaa(201, ["viesti" => "Lisätty", "id" => $conn->lastInsertId()]);
            break;
 
        case 'PUT':   // muokkaus
            if (!$id)   vastaa(400, ["virhe" => "id puuttuu"]);
            if (!$data) vastaa(400, ["virhe" => "Ei tietoja"]);
 
            $s = $conn->prepare("SELECT * FROM $taulu WHERE id = ?");
            $s->execute([$id]);
            $vanha = $s->fetch(PDO::FETCH_ASSOC);
            if (!$vanha) vastaa(404, ["virhe" => "Ei löytynyt"]);
 
            $virheet = tarkista($conn, $taulu, $data, $saannot, $valinnaiset, $vanha);
            if ($virheet) vastaa(422, ["virheet" => $virheet]);
 
            $asetukset = implode(",", array_map(fn($k) => "$k = ?", array_keys($data)));
            $s = $conn->prepare("UPDATE $taulu SET $asetukset WHERE id = ?");
            $s->execute([...array_values($data), $id]);
            vastaa(200, ["viesti" => "Päivitetty"]);
            break;
 
        case 'DELETE':  // poisto (varauksilla peruminen)
            if (!$id) vastaa(400, ["virhe" => "id puuttuu"]);
 
            $s = $conn->prepare("SELECT id FROM $taulu WHERE id = ?");
            $s->execute([$id]);
            if (!$s->fetch()) vastaa(404, ["virhe" => "Ei löytynyt"]);
 
            if ($taulu === "Varaukset") {
                $conn->prepare("UPDATE Varaukset SET tila = 0 WHERE id = ?")->execute([$id]);
                vastaa(200, ["viesti" => "Varaus peruttu"]);
            }
            $conn->prepare("DELETE FROM $taulu WHERE id = ?")->execute([$id]);
            vastaa(200, ["viesti" => "Poistettu"]);
            break;
 
        default:
            vastaa(405, ["virhe" => "Metodi ei ole sallittu"]);
    }
} catch (PDOException $e) {
    // 23000 = viiteavainvirhe (esim. asiakkaalla on varauksia)
    if ($e->getCode() == 23000) {
        vastaa(409, ["virhe" => "Ei voi poistaa, koska tietoon liittyy varauksia"]);
    }
    vastaa(500, ["virhe" => "Palvelinvirhe"]);
}
 