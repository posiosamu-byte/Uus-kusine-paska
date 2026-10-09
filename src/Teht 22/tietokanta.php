<?php
$servername = "db";
$username = "root";
$password = "root";
$dbname = "Hotelli";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect. " . $e->getMessage());
}