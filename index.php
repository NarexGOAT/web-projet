<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

// Nombre total d'offres
$sqlCountOffres = "SELECT COUNT(*) FROM offre";
$total_offres = (int) $pdo->query($sqlCountOffres)->fetchColumn();

// Nombre total d'entreprises
$sqlCountEntreprises = "SELECT COUNT(*) FROM entreprise";
$total_entreprises = (int) $pdo->query($sqlCountEntreprises)->fetchColumn();

// 3 dernières offres (avec entreprise)
$sqlDernieres = "
    SELECT 
        o.*,
        e.nom_entreprise
    FROM offre o
    LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
    ORDER BY o.date_publication DESC
    LIMIT 3
";
$dernieres_offres = $pdo->query($sqlDernieres)->fetchAll(PDO::FETCH_ASSOC);

echo $twig->render('index.html.twig', [
    'total_offres'      => $total_offres,
    'total_entreprises' => $total_entreprises,
    'dernieres_offres'  => $dernieres_offres,
]);