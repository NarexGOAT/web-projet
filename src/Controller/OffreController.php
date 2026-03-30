<?php

class OffreController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    /* --------- ACCES CREATION / MODIF --------- */
    private function checkAccessManage(): void
    {
        if (empty($_SESSION['user_id'])
            || empty($_SESSION['role'])
            || !in_array($_SESSION['role'], ['admin', 'recruteur'], true)) {
            header('Location: index.php?page=offres');
            exit;
        }
    }

    /* --------- LISTE (ton code existant) --------- */
    public function liste(): void
    {
        $sqlComp = "
            SELECT DISTINCT competence
            FROM offre
            WHERE competence IS NOT NULL AND competence <> ''
            ORDER BY competence
        ";
        $competences = $this->pdo->query($sqlComp)->fetchAll(\PDO::FETCH_COLUMN);

        $sqlVille = "
            SELECT DISTINCT e.ville
            FROM entreprise e
            JOIN offre o ON o.id_entreprise = e.id_entreprise
            WHERE e.ville IS NOT NULL AND e.ville <> ''
            ORDER BY e.ville
        ";
        $villes = $this->pdo->query($sqlVille)->fetchAll(\PDO::FETCH_COLUMN);

        $sql = "
            SELECT 
                o.*,
                e.nom_entreprise,
                e.ville
            FROM offre o
            LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
        ";

        $conditions = [];
        $params = [];

        if (!empty($_GET['metier'])) {
            $conditions[] = 'o.titre LIKE :metier';
            $params['metier'] = '%' . $_GET['metier'] . '%';
        }

        if (!empty($_GET['ville']) && $_GET['ville'] !== 'Toutes les villes') {
            $conditions[] = 'e.ville = :ville';
            $params['ville'] = $_GET['ville'];
        }

        if (!empty($_GET['duree']) && $_GET['duree'] !== 'Toutes les durées') {
            if ($_GET['duree'] === 'Moins de 2 mois') {
                $conditions[] = 'o.duree_stage < 8';
            } elseif ($_GET['duree'] === '2 à 4 mois') {
                $conditions[] = 'o.duree_stage BETWEEN 8 AND 16';
            } elseif ($_GET['duree'] === '4 à 6 mois') {
                $conditions[] = 'o.duree_stage BETWEEN 16 AND 24';
            } elseif ($_GET['duree'] === 'Plus de 6 mois') {
                $conditions[] = 'o.duree_stage > 24';
            }
        }

        if (!empty($_GET['competence']) && $_GET['competence'] !== 'Toutes les compétences') {
            $conditions[] = 'o.competence = :competence';
            $params['competence'] = $_GET['competence'];
        }

        if (!empty($_GET['publication']) && $_GET['publication'] !== 'Toutes les dates') {
            if ($_GET['publication'] === 'Aujourd’hui') {
                $conditions[] = 'DATE(o.date_publication) = CURDATE();
 ';
            } elseif ($_GET['publication'] === 'Cette semaine') {
                $conditions[] = 'YEARWEEK(o.date_publication, 1) = YEARWEEK(CURDATE(), 1)';
            } elseif ($_GET['publication'] === 'Ce mois-ci') {
                $conditions[] = 'YEAR(o.date_publication) = YEAR(CURDATE())
                                 AND MONTH(o.date_publication) = MONTH(CURDATE())';
            }
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY o.date_publication DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $offres = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('liste-offres.html.twig', [
            'offres'        => $offres,
            'nombre_offres' => count($offres),
            'filtres'       => $_GET,
            'competences'   => $competences,
            'villes'        => $villes,
        ]);
    }

    /* --------- DETAIL (ton code existant) --------- */
    public function detail(int $id): void
    {
        $sql = '
            SELECT o.*, e.nom_entreprise, e.ville
            FROM offre o
            LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            WHERE o.id_offre = :id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $offre = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$offre) {
            http_response_code(404);
            echo 'Offre introuvable';
            return;
        }

        $dejaCandidature = false;
        if (!empty($_SESSION['user_id'])) {
            $idUser = (int) $_SESSION['user_id'];

            $sqlCheck = "
                SELECT id_candidature 
                FROM candidature
                WHERE id_user = :id_user AND id_offre = :id_offre
                LIMIT 1";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([
                'id_user'  => $idUser,
                'id_offre' => $id,
            ]);
            $dejaCandidature = (bool) $stmtCheck->fetch();
        }

        echo $this->twig->render('offre.html.twig', [
            'offre'            => $offre,
            'deja_candidature' => $dejaCandidature,
        ]);
    }

    /* --------- CREER OFFRE (NOUVEAU) --------- */

    // GET : afficher formulaire
    public function creerForm(): void
    {
        $this->checkAccessManage();

        $sql = "
            SELECT id_entreprise, nom_entreprise, ville
            FROM entreprise
            ORDER BY nom_entreprise
        ";
        $entreprises = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('creer_offre.html.twig', [
            'entreprises' => $entreprises,
            'old'         => [],
        ]);
    }

    // POST : traiter formulaire
    public function creerSubmit(): void
    {
        $this->checkAccessManage();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=offre-creer');
            exit;
        }

        $titre        = trim($_POST['titre'] ?? '');
        $competence   = trim($_POST['competence'] ?? '');
        $type_offre   = trim($_POST['type_offre'] ?? '');
        $duree_stage  = (int) ($_POST['duree_stage'] ?? 0);
        $remuneration = (float) ($_POST['remuneration'] ?? 0);
        $idEntreprise = (int) ($_POST['id_entreprise'] ?? 0);
        $description  = trim($_POST['description'] ?? '');

        $erreurs = [];

        if ($titre === '')        $erreurs[] = 'Le titre est obligatoire.';
        if ($competence === '')   $erreurs[] = 'La compétence est obligatoire.';
        if ($type_offre === '')   $erreurs[] = 'Le type d\'offre est obligatoire.';
        if ($duree_stage <= 0)    $erreurs[] = 'La durée doit être positive.';
        if ($remuneration < 0)    $erreurs[] = 'La rémunération doit être positive.';
        if ($idEntreprise <= 0)   $erreurs[] = 'Veuillez choisir une entreprise.';
        if ($description === '')  $erreurs[] = 'La description est obligatoire.';

        if (!empty($erreurs)) {
            $sql = "
                SELECT id_entreprise, nom_entreprise, ville
                FROM entreprise
                ORDER BY nom_entreprise
            ";
            $entreprises = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            echo $this->twig->render('creer_offre.html.twig', [
                'entreprises' => $entreprises,
                'old' => [
                    'titre'         => $titre,
                    'competence'    => $competence,
                    'type_offre'    => $type_offre,
                    'duree_stage'   => $duree_stage,
                    'remuneration'  => $remuneration,
                    'id_entreprise' => $idEntreprise,
                    'description'   => $description,
                ],
                'erreurs' => $erreurs,
            ]);
            return;
        }

        $sqlInsert = "
            INSERT INTO offre (titre, competence, type_offre, duree_stage, remuneration, description, id_entreprise, date_publication)
            VALUES (:titre, :competence, :type_offre, :duree_stage, :remuneration, :description, :id_entreprise, NOW())
        ";
        $stmt = $this->pdo->prepare($sqlInsert);
        $stmt->execute([
            'titre'         => $titre,
            'competence'    => $competence,
            'type_offre'    => $type_offre,
            'duree_stage'   => $duree_stage,
            'remuneration'  => $remuneration,
            'description'   => $description,
            'id_entreprise' => $idEntreprise,
        ]);

        header('Location: index.php?page=offres');
        exit;
    }

    // GET : afficher formulaire de modification
public function modifierForm(int $id): void
{
    $this->checkAccessManage();

    $sql = "
        SELECT *
        FROM offre
        WHERE id_offre = :id
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $offre = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$offre) {
        http_response_code(404);
        echo 'Offre introuvable';
        return;
    }

    $sqlEnt = "
        SELECT id_entreprise, nom_entreprise, ville
        FROM entreprise
        ORDER BY nom_entreprise
    ";
    $entreprises = $this->pdo->query($sqlEnt)->fetchAll(\PDO::FETCH_ASSOC);

    echo $this->twig->render('modifier_offre.html.twig', [
        'offre'       => $offre,
        'entreprises' => $entreprises,
    ]);
}

// POST : traiter modification ou suppression
public function modifierSubmit(int $id): void
{
    $this->checkAccessManage();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=offre-modifier&id=' . $id);
        exit;
    }

    // suppression
    if (!empty($_POST['supprimer'])) {
        $sqlDel = "DELETE FROM offre WHERE id_offre = :id";
        $stmtDel = $this->pdo->prepare($sqlDel);
        $stmtDel->execute(['id' => $id]);

        header('Location: index.php?page=offres');
        exit;
    }

    // mise à jour
    $titre        = trim($_POST['titre'] ?? '');
    $competence   = trim($_POST['competence'] ?? '');
    $type_offre   = trim($_POST['type_offre'] ?? '');
    $duree_stage  = (int) ($_POST['duree_stage'] ?? 0);
    $remuneration = (float) ($_POST['remuneration'] ?? 0);
    $idEntreprise = (int) ($_POST['id_entreprise'] ?? 0);
    $description  = trim($_POST['description'] ?? '');

    $sql = "
        UPDATE offre
        SET titre        = :titre,
            competence   = :competence,
            type_offre   = :type_offre,
            duree_stage  = :duree_stage,
            remuneration = :remuneration,
            id_entreprise= :id_entreprise,
            description  = :description
        WHERE id_offre = :id
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'titre'         => $titre,
        'competence'    => $competence,
        'type_offre'    => $type_offre,
        'duree_stage'   => $duree_stage,
        'remuneration'  => $remuneration,
        'id_entreprise' => $idEntreprise,
        'description'   => $description,
        'id'            => $id,
    ]);

    header('Location: index.php?page=offre&id=' . $id);
    exit;
}
}