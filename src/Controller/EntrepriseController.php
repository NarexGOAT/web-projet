<?php

class EntrepriseController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    private function checkAccessCreate(): void
{
    if (empty($_SESSION['user_id']) 
        || empty($_SESSION['role']) 
        || !in_array($_SESSION['role'], ['admin', 'recruteur'], true)
    ) {
        header('Location: index.php?page=entreprises');
        exit;
    }
}


    public function liste(): void
    {
        $sqlDomaines = "
            SELECT DISTINCT domaine
            FROM entreprise
            WHERE domaine IS NOT NULL AND domaine <> ''
            ORDER BY domaine
        ";
        $domaines = $this->pdo->query($sqlDomaines)->fetchAll(\PDO::FETCH_COLUMN);

        $sqlVilles = "
            SELECT DISTINCT ville
            FROM entreprise
            WHERE ville IS NOT NULL AND ville <> ''
            ORDER BY ville
        ";
        $villes = $this->pdo->query($sqlVilles)->fetchAll(\PDO::FETCH_COLUMN);

        $sql = "
            SELECT 
                e.*,
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
                $conditions[] = 'e.taille < 50';
            } elseif ($_GET['taille'] === 'PME') {
                $conditions[] = 'e.taille >= 50 AND e.taille < 250';
            } elseif ($_GET['taille'] === 'Grande entreprise') {
                $conditions[] = 'e.taille >= 250';
            }
        }

        if (!empty($_GET['offres']) && $_GET['offres'] !== 'Peu importe') {
            if ($_GET['offres'] === '0 offre') {
                $conditions[] = 'COUNT(o.id_offre) = 0';
            } elseif ($_GET['offres'] === '1 à 5 offres') {
                $conditions[] = 'COUNT(o.id_offre) BETWEEN 1 AND 5';
            } elseif ($_GET['offres'] === '6 à 10 offres') {
                $conditions[] = 'COUNT(o.id_offre) BETWEEN 6 AND 10';
            } elseif ($_GET['offres'] === 'Plus de 10 offres') {
                $conditions[] = 'COUNT(o.id_offre) > 10';
            }
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY e.id_entreprise ORDER BY e.nom_entreprise';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $entreprises = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('liste-entreprises.html.twig', [
            'entreprises'        => $entreprises,
            'nombre_entreprises' => count($entreprises),
            'filtres'            => $_GET,
            'domaines'           => $domaines,
            'villes'             => $villes,
        ]);
    }

    public function detail(int $id): void
    {
        $sqlEnt = "
            SELECT *
            FROM entreprise
            WHERE id_entreprise = :id
        ";
        $stmt = $this->pdo->prepare($sqlEnt);
        $stmt->execute(['id' => $id]);
        $entreprise = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$entreprise) {
            http_response_code(404);
            echo 'Entreprise introuvable';
            return;
        }

        $sqlNote = "
            SELECT AVG(note) AS moyenne
            FROM evaluation
            WHERE id_entreprise = :id";
        $stmtNote = $this->pdo->prepare($sqlNote);
        $stmtNote->execute(['id' => $id]);
        $moyenne_note = $stmtNote->fetchColumn();

        $sqlOffres = "
            SELECT *
            FROM offre
            WHERE id_entreprise = :id
            ORDER BY date_publication DESC
        ";
        $stmtOffres = $this->pdo->prepare($sqlOffres);
        $stmtOffres->execute(['id' => $id]);
        $offres = $stmtOffres->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('entreprise.html.twig', [
            'entreprise'   => $entreprise,
            'moyenne_note' => $moyenne_note,
            'offres'       => $offres,
            'offre_count'  => count($offres),
        ]);
    }

    public function creerForm(): void
    {
        $this->checkAccessCreate();

        echo $this->twig->render('creer-entreprise.html.twig', []);
    }

    public function creerSubmit(): void
    {
        $this->checkAccessCreate();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=entreprise-creer');
            exit;
        }

        $nom         = $_POST['nom_entreprise'] ?? '';
        $domaine     = $_POST['domaine'] ?? '';
        $taille      = (int) ($_POST['taille'] ?? 0);
        $ville       = $_POST['ville'] ?? '';
        $telephone   = $_POST['telephone'] ?? '';
        $email       = $_POST['email'] ?? '';
        $description = $_POST['description'] ?? '';

        $sql = "
            INSERT INTO entreprise (nom_entreprise, domaine, taille, ville, telephone, email, description)
            VALUES (:nom, :domaine, :taille, :ville, :telephone, :email, :description)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nom'         => $nom,
            'domaine'     => $domaine,
            'taille'      => $taille,
            'ville'       => $ville,
            'telephone'   => $telephone,
            'email'       => $email,
            'description' => $description,
        ]);

        header('Location: index.php?page=entreprises');
        exit;
    }

    private function checkAccessManage(): void
{
    if (empty($_SESSION['user_id']) 
        || empty($_SESSION['role']) 
        || !in_array($_SESSION['role'], ['admin', 'recruteur'], true)) {
        header('Location: index.php?page=entreprises');
        exit;
    }
}

// afficher le formulaire de modification
public function modifierForm(int $id): void
{
    $this->checkAccessManage();

    $sql = "
        SELECT *
        FROM entreprise
        WHERE id_entreprise = :id
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $entreprise = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$entreprise) {
        http_response_code(404);
        echo 'Entreprise introuvable';
        return;
    }

    echo $this->twig->render('modifier_entreprise.html.twig', [
        'entreprise' => $entreprise,
    ]);
}

// traiter le POST (update ou suppression)
public function modifierSubmit(int $id): void
{
    $this->checkAccessManage();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=entreprise-modifier&id=' . $id);
        exit;
    }

    // suppression
    if (!empty($_POST['supprimer'])) {
        $sqlDel = "DELETE FROM entreprise WHERE id_entreprise = :id";
        $stmtDel = $this->pdo->prepare($sqlDel);
        $stmtDel->execute(['id' => $id]);

        header('Location: index.php?page=entreprises');
        exit;
    }

    // mise à jour
    $nom         = $_POST['nom_entreprise'] ?? '';
    $domaine     = $_POST['domaine'] ?? '';
    $taille      = (int) ($_POST['taille'] ?? 0);
    $ville       = $_POST['ville'] ?? '';
    $telephone   = $_POST['telephone'] ?? '';
    $email       = $_POST['email'] ?? '';
    $description = $_POST['description'] ?? '';

    $sql = "
        UPDATE entreprise
        SET nom_entreprise = :nom,
            domaine        = :domaine,
            taille         = :taille,
            ville          = :ville,
            telephone      = :telephone,
            email          = :email,
            description    = :description
        WHERE id_entreprise = :id
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'nom'         => $nom,
        'domaine'     => $domaine,
        'taille'      => $taille,
        'ville'       => $ville,
        'telephone'   => $telephone,
        'email'       => $email,
        'description' => $description,
        'id'          => $id,
    ]);

    header('Location: index.php?page=entreprise&id=' . $id);
    exit;
}

}

public function evaluerSubmit(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=connexion');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=entreprises');
        exit;
    }

    $note = (int) ($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');
    $id_entreprise = (int) ($_POST['id_entreprise'] ?? 0);
    $id_user = (int) $_SESSION['user_id'];

    if ($note < 1 || $note > 5 || $id_entreprise <= 0 || $commentaire === '') {
        header('Location: index.php?page=entreprise&id=' . $id_entreprise);
        exit;
    }

    $sql = "
        INSERT INTO evaluation (note, commentaire, id_user, id_entreprise)
        VALUES (:note, :commentaire, :id_user, :id_entreprise)
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'note' => $note,
        'commentaire' => $commentaire,
        'id_user' => $id_user,
        'id_entreprise' => $id_entreprise,
    ]);

    header('Location: index.php?page=entreprise&id=' . $id_entreprise);
    exit;
}