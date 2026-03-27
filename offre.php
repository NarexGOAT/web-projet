<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

// id de l'offre dans l'URL: offre.php?id=...
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sql = 'SELECT o.*, e.nom_entreprise 
        FROM offre o
        LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
        WHERE o.id_offre = :id';

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offre) {
    http_response_code(404);
    echo 'Offre introuvable';
    exit;
}

echo $twig->render('offre.html.twig', [
    'offre' => $offre,
]);