<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

// Récupérer toutes les entreprises
$sql = 'SELECT * FROM entreprise';
$stmt = $pdo->query($sql);
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo $twig->render('liste-entreprises.html.twig', [
    'entreprises' => $entreprises,
    'nombre_entreprises' => count($entreprises),
]);