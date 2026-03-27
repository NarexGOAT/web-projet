<?php
require_once __DIR__ . '/twig.php';
require_once __DIR__ . '/database.php';

// Tableau de conditions SQL et de paramètres
$conditions = [];
$params = [];

// ----- FILTRES ----- //

// Métier recherché : on filtre sur titre OU description
if (!empty($_GET['metier'])) {
    $conditions[] = '(o.titre LIKE :metier OR o.description LIKE :metier)';
    $params['metier'] = '%' . $_GET['metier'] . '%';
}

// Compétence : on filtre sur la colonne competence (texte libre)
if (!empty($_GET['competence']) && $_GET['competence'] !== 'Toutes les compétences') {
    $conditions[] = 'o.competence LIKE :competence';
    $params['competence'] = '%' . $_GET['competence'] . '%';
}

// Durée : ta colonne duree_stage est un VARCHAR,
// pour l'instant on fait un match simple sur le texte choisi dans la liste
if (!empty($_GET['duree']) && $_GET['duree'] !== 'Toutes les durées') {
    $conditions[] = 'o.duree_stage = :duree';
    $params['duree'] = $_GET['duree'];
}

// Date de publication : on fait des intervalles sur date_publication
if (!empty($_GET['publication']) && $_GET['publication'] !== 'Toutes les dates') {

    if ($_GET['publication'] === 'Aujourd’hui') {
        $conditions[] = 'DATE(o.date_publication) = CURDATE()';
    }

    if ($_GET['publication'] === 'Cette semaine') {
        // du lundi de cette semaine à dimanche
        $conditions[] = 'YEARWEEK(o.date_publication, 1) = YEARWEEK(CURDATE(), 1)';
    }

    if ($_GET['publication'] === 'Ce mois-ci') {
        $conditions[] = 'YEAR(o.date_publication) = YEAR(CURDATE())
                         AND MONTH(o.date_publication) = MONTH(CURDATE())';
    }
}

$sql = 'SELECT o.*, e.nom_entreprise
        FROM offre o
        LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise';

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY o.date_publication DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlComp = 'SELECT DISTINCT competence FROM offre WHERE competence IS NOT NULL AND competence <> "" ORDER BY competence';
$stmtComp = $pdo->query($sqlComp);
$competences = $stmtComp->fetchAll(PDO::FETCH_COLUMN);
echo $twig->render('liste-offres.html.twig', [
    'offres'         => $offres,
    'nombre_offres'  => count($offres),
    'filtres'        => $_GET,
    'competences'    => $competences,
]);