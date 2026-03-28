<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

if (empty($_GET['id'])) {
    header('Location: liste-entreprises.php');
    exit;
}

$id = (int) $_GET['id'];

// 1. Récupérer l'entreprise
$sqlEntreprise = "
    SELECT *
    FROM entreprise
    WHERE id_entreprise = :id
";
$stmt = $pdo->prepare($sqlEntreprise);
$stmt->execute(['id' => $id]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    header('Location: liste-entreprises.php');
    exit;
}

// 2. Récupérer les offres de cette entreprise
$sqlOffres = "
    SELECT *
    FROM offre
    WHERE id_entreprise = :id
    ORDER BY date_publication DESC
";
$stmt = $pdo->prepare($sqlOffres);
$stmt->execute(['id' => $id]);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);
$offre_count = count($offres);

// 3. Calculer la moyenne des évaluations (table evaluation)
$sqlMoyenne = "
    SELECT AVG(note) AS moyenne
    FROM evaluation
    WHERE id_entreprise = :id
";
$stmt = $pdo->prepare($sqlMoyenne);
$stmt->execute(['id' => $id]);
$moyenne_note = $stmt->fetchColumn(); // peut être null s'il n'y a pas d'éval

echo $twig->render('entreprise.html.twig', [
    'entreprise'    => $entreprise,
    'offres'        => $offres,
    'offre_count'   => $offre_count,
    'moyenne_note'  => $moyenne_note,
]);