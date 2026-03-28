<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

if (empty($_GET['id'])) {
    header('Location: liste-offres.php');
    exit;
}

$id = (int) $_GET['id'];

$sql = "
    SELECT 
        o.*,
        e.nom_entreprise,
        e.ville
    FROM offre o
    LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
    WHERE o.id_offre = :id
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offre) {
    // Offre introuvable
    header('Location: liste-offres.php');
    exit;
}

echo $twig->render('postuler.html.twig', [
    'offre' => $offre,
]);