<?php

class OffreController
{
    private $pdo;
    private $twig;

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    // =========================
    // 🔎 DETAIL OFFRE
    // =========================
    public function detail()
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            die("Offre introuvable");
        }

        $stmt = $this->pdo->prepare("
            SELECT o.*, e.nom_entreprise, e.ville, e.id_entreprise
            FROM offre o
            JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            WHERE o.id_offre = :id
        ");

        $stmt->execute([':id' => $id]);
        $offre = $stmt->fetch();

        if (!$offre) {
            die("Offre introuvable");
        }

        echo $this->twig->render('offre.html.twig', [
            'offre' => $offre
        ]);
    }

    // =========================
    // 📋 LISTE OFFRES + FILTRES
    // =========================
    public function liste()
    {
        // =========================
        // 📄 PAGINATION
        // =========================
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $limit = 6;
        $offset = ($page - 1) * $limit;

        // =========================
        // 🔎 FILTRES
        // =========================
        $recherche = trim($_GET['recherche'] ?? '');
        $competence = trim($_GET['competence'] ?? '');
        $ville = trim($_GET['ville'] ?? '');

        $conditions = [];
        $params = [];

        if ($recherche !== '') {
            $conditions[] = "o.titre LIKE :recherche";
            $params[':recherche'] = "%$recherche%";
        }

        if ($competence !== '') {
            $conditions[] = "o.competence = :competence";
            $params[':competence'] = $competence;
        }

        if ($ville !== '') {
            $conditions[] = "e.ville = :ville";
            $params[':ville'] = $ville;
        }

        $where = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : "";

        // =========================
        // 📦 REQUETE OFFRES
        // =========================
        $sql = "
            SELECT o.*, e.nom_entreprise, e.ville
            FROM offre o
            JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            $where
            ORDER BY o.date_publication DESC
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $offres = $stmt->fetchAll();

        // =========================
        // 🔢 COUNT TOTAL
        // =========================
        $sqlCount = "
            SELECT COUNT(*)
            FROM offre o
            JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            $where
        ";

        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        $totalPages = max(1, ceil($total / $limit));

        // =========================
        // 📍 FILTRES (LISTES)
        // =========================
        $villes = $this->pdo->query("
            SELECT DISTINCT ville 
            FROM entreprise 
            ORDER BY ville
        ")->fetchAll(PDO::FETCH_COLUMN);

        $competences = $this->pdo->query("
            SELECT DISTINCT competence 
            FROM offre 
            ORDER BY competence
        ")->fetchAll(PDO::FETCH_COLUMN);

        // =========================
        // 🎯 RENDER
        // =========================
        echo $this->twig->render('liste-offres.html.twig', [
            'offres' => $offres,
            'page' => $page,
            'totalPages' => $totalPages,
            'nombre_offres' => $total,
            'villes' => $villes,
            'competences' => $competences,

            'filtres' => [
                'recherche' => $recherche,
                'competence' => $competence,
                'ville' => $ville
            ]
        ]);
    }

    // =========================
    // 🏢 GESTION OFFRES
    // =========================
    public function gestion(): void
    {
        $stmt = $this->pdo->query("
            SELECT o.id_offre, o.titre, o.duree_stage, o.competence, e.nom_entreprise
            FROM offre o
            JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            ORDER BY o.titre ASC
        ");

        $offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo $this->twig->render('gestion-offres.html.twig', [
            'offres' => $offres
        ]);
    }

    // =========================
    // ➕ CREER OFFRE
    // =========================
    public function creer(): void
    {
        $entreprises = $this->pdo->query("
            SELECT id_entreprise, nom_entreprise
            FROM entreprise
            ORDER BY nom_entreprise ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $competence = trim($_POST['competence'] ?? '');
            $dureeStage = trim($_POST['duree_stage'] ?? '');
            $idEntreprise = (int) ($_POST['id_entreprise'] ?? 0);

            if (
                empty($titre) ||
                empty($description) ||
                empty($competence) ||
                empty($dureeStage) ||
                $idEntreprise <= 0
            ) {
                echo $this->twig->render('creer-offre.html.twig', [
                    'erreur' => 'Tous les champs sont obligatoires.',
                    'offre' => [
                        'titre' => $titre,
                        'description' => $description,
                        'competence' => $competence,
                        'duree_stage' => $dureeStage,
                        'id_entreprise' => $idEntreprise
                    ],
                    'entreprises' => $entreprises
                ]);
                return;
            }

            $sql = "
                INSERT INTO offre (titre, description, competence, duree_stage, date_publication, id_entreprise)
                VALUES (:titre, :description, :competence, :duree_stage, NOW(), :id_entreprise)
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':competence' => $competence,
                ':duree_stage' => $dureeStage,
                ':id_entreprise' => $idEntreprise
            ]);

            header('Location: index.php?page=gestion-offres&success=creation');
            exit;
        }

        echo $this->twig->render('creer-offre.html.twig', [
            'offre' => null,
            'entreprises' => $entreprises
        ]);
    }

    // =========================
    // ✏️ MODIFIER OFFRE
    // =========================
    public function modifier(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=gestion-offres');
            exit;
        }

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM offre
            WHERE id_offre = ?
        ");
        $stmt->execute([$id]);
        $offre = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offre) {
            header('Location: index.php?page=gestion-offres');
            exit;
        }

        $entreprises = $this->pdo->query("
            SELECT id_entreprise, nom_entreprise
            FROM entreprise
            ORDER BY nom_entreprise ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $competence = trim($_POST['competence'] ?? '');
            $dureeStage = trim($_POST['duree_stage'] ?? '');
            $idEntreprise = (int) ($_POST['id_entreprise'] ?? 0);

            if (
                empty($titre) ||
                empty($description) ||
                empty($competence) ||
                empty($dureeStage) ||
                $idEntreprise <= 0
            ) {
                echo $this->twig->render('modifier-offre.html.twig', [
                    'erreur' => 'Tous les champs sont obligatoires.',
                    'offre' => [
                        'id_offre' => $id,
                        'titre' => $titre,
                        'description' => $description,
                        'competence' => $competence,
                        'duree_stage' => $dureeStage,
                        'id_entreprise' => $idEntreprise
                    ],
                    'entreprises' => $entreprises
                ]);
                return;
            }

            $sql = "
                UPDATE offre
                SET titre = :titre,
                    description = :description,
                    competence = :competence,
                    duree_stage = :duree_stage,
                    id_entreprise = :id_entreprise
                WHERE id_offre = :id_offre
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':competence' => $competence,
                ':duree_stage' => $dureeStage,
                ':id_entreprise' => $idEntreprise,
                ':id_offre' => $id
            ]);

            header('Location: index.php?page=gestion-offres&success=modification');
            exit;
        }

        echo $this->twig->render('modifier-offre.html.twig', [
            'offre' => $offre,
            'entreprises' => $entreprises
        ]);
    }

    // =========================
    // 🗑️ SUPPRIMER OFFRE
    // =========================
    public function supprimer(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=gestion-offres');
            exit;
        }

        $stmt = $this->pdo->prepare("
            SELECT id_offre
            FROM offre
            WHERE id_offre = ?
        ");
        $stmt->execute([$id]);
        $offre = $stmt->fetch();

        if (!$offre) {
            header('Location: index.php?page=gestion-offres');
            exit;
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM offre
            WHERE id_offre = ?
        ");
        $stmt->execute([$id]);

        header('Location: index.php?page=gestion-offres&success=suppression');
        exit;
    }
}