<?php
require '../config.php';

echo "<h1>Etape 2 fonctionne!</h1>";
$pdo = getConnection();
var_dump($pdo); // Vérifie si connexion BDD ok
