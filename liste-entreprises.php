<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

// Conditions & paramètres
$conditions = [];
$params = [];

// Filtre sur le nom
if (!empty($_GET['nom'])) {
    $conditions[] = 'e.nom_entreprise LIKE :nom';
    $params['nom'] = '%' . $_GET['nom'] . '%';
}

// Filtre sur le nombre d'offres (optionnel)
if (!empty($_GET['offres']) && $_GET['offres'] !== 'Peu importe') {
    if ($_GET['offres'] === '1 à 5 offres') {
        $conditions[] = 'nb_offres BETWEEN 1 AND 5';
    } elseif ($_GET['offres'] === '6 à 10 offres') {
        $conditions[] = 'nb_offres BETWEEN 6 AND 10';
    } elseif ($_GET['offres'] === 'Plus de 10 offres') {
        $conditions[] = 'nb_offres > 10';
    }
}

// Requête principale : entreprises + nombre d'offres
$sql = 'SELECT e.*,
               COUNT(o.id_offre) AS nb_offres
        FROM entreprise e
        LEFT JOIN offre o ON o.id_entreprise = e.id_entreprise
        GROUP BY e.id_entreprise';

if ($conditions) {
    // on ne peut pas mettre WHERE après GROUP BY,
    // donc on utilise HAVING pour les conditions sur nb_offres ou nom_entreprise
    $sql .= ' HAVING ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY e.nom_entreprise ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo $twig->render('liste-entreprises.html.twig', [
    'entreprises'        => $entreprises,
    'nombre_entreprises' => count($entreprises),
    'filtres'            => $_GET,
]);