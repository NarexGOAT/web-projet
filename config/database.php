<?php

$host = 'localhost';
$dbname = 'u593710153_allostagedb';
$username = 'u593710153_allostage';
$password = 'PourandeePrime2017!';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}