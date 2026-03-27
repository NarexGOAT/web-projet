<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php'; 

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sqlEntreprise = 'SELECT * FROM entreprise WHERE id_entreprise = :id';
$stmt = $pdo->prepare($sqlEntreprise);
$stmt->execute(['id' => $id]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    http_response_code(404);
    echo 'Entreprise introuvable';
    exit;
}

$sqlOffres = 'SELECT * FROM offre WHERE id_entreprise = :id';
$stmt = $pdo->prepare($sqlOffres);
$stmt->execute(['id' => $id]);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo $twig->render('entreprise.html.twig', [
    'entreprise'  => $entreprise,
    'offres'      => $offres,
    'offre_count' => count($offres),
]);