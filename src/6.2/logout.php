<?php
require_once 'db.php';
// Ulos kirjautumisen koodi
session_start();
session_unset();
session_destroy();
header("Location: kirjaudu.html");
exit();
?>