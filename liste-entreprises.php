<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

$sqlDomaine = "
    SELECT DISTINCT domaine
    FROM entreprise
    WHERE domaine IS NOT NULL AND domaine <> ''
    ORDER BY domaine
";
$domaines = $pdo->query($sqlDomaine)->fetchAll(PDO::FETCH_COLUMN);

$sqlVille = "
    SELECT DISTINCT ville
    FROM entreprise
    WHERE ville IS NOT NULL AND ville <> ''
    ORDER BY ville
";
$villes = $pdo->query($sqlVille)->fetchAll(PDO::FETCH_COLUMN);

$sql = "
    SELECT e.*,
           COUNT(o.id_offre) AS nb_offres
    FROM entreprise e
    LEFT JOIN offre o ON o.id_entreprise = e.id_entreprise
";

$conditions = [];
$params = [];

if (!empty($_GET['nom'])) {
    $conditions[] = 'e.nom_entreprise LIKE :nom';
    $params['nom'] = '%' . $_GET['nom'] . '%';
}

if (!empty($_GET['domaine']) && $_GET['domaine'] !== 'Tous les domaines') {
    $conditions[] = 'e.domaine = :domaine';
    $params['domaine'] = $_GET['domaine'];
}

if (!empty($_GET['ville']) && $_GET['ville'] !== 'Toutes les villes') {
    $conditions[] = 'e.ville = :ville';
    $params['ville'] = $_GET['ville'];
}

if (!empty($_GET['taille']) && $_GET['taille'] !== 'Toutes les tailles') {
    if ($_GET['taille'] === 'Petite entreprise') {
        $conditions[] = 'e.taille BETWEEN 1 AND 49';
    } elseif ($_GET['taille'] === 'PME') {
        $conditions[] = 'e.taille BETWEEN 50 AND 249';
    } elseif ($_GET['taille'] === 'Grande entreprise') {
        $conditions[] = 'e.taille >= 250';
    }
}

if (!empty($_GET['offres']) && $_GET['offres'] !== 'Peu importe') {
    if ($_GET['offres'] === '0 offre') {
        $conditions[] = 'nb_offres = 0';
    } elseif ($_GET['offres'] === '1 à 5 offres') {
        $conditions[] = 'nb_offres BETWEEN 1 AND 5';
    } elseif ($_GET['offres'] === '6 à 10 offres') {
        $conditions[] = 'nb_offres BETWEEN 6 AND 10';
    } elseif ($_GET['offres'] === 'Plus de 10 offres') {
        $conditions[] = 'nb_offres > 10';
    }
}

$sql .= ' GROUP BY e.id_entreprise';

if ($conditions) {
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
    'domaines'           => $domaines,
    'villes'             => $villes,
]);