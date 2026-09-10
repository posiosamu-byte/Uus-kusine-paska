<?php
echo "Docker toimii!<br>";

try {
    $pdo = new PDO(
        'mysql:host=db;dbname=testdb;charset=utf8',
        'user',
        'pass'
    );
    echo "Tietokantayhteys toimii!";
} catch (PDOException $e) {
    echo "Virhe: " . $e->getMessage();
}
