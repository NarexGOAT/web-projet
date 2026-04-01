<?php

class EntrepriseController
{
    private $pdo;
    private $twig;

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    // =========================
    // 🔒 VERIFICATION ROLE
    // =========================
    private function verifierAccesAdminOuPilote(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            empty($_SESSION['user']) ||
            empty($_SESSION['user']['role']) ||
            !in_array($_SESSION['user']['role'], ['admin', 'pilote'])
        ) {
            header('Location: index.php?page=accueil');
            exit;
        }
    }

    // =========================
    // 🔎 DETAIL ENTREPRISE
    // =========================
    public function detail()
    {
        $id = $_GET['id'];

        $stmt = $this->pdo->prepare("
            SELECT * FROM entreprise WHERE id_entreprise = ?
        ");
        $stmt->execute([$id]);
        $entreprise = $stmt->fetch();

        $stmt = $this->pdo->prepare("
            SELECT * FROM offre WHERE id_entreprise = ?
        ");
        $stmt->execute([$id]);
        $offres = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("
            SELECT 
                ROUND(AVG(note),1) as moyenne,
                COUNT(*) as total
            FROM evaluation
            WHERE id_entreprise = ?
        ");
        $stmt->execute([$id]);
        $evaluation = $stmt->fetch();

        if (!$evaluation || $evaluation['moyenne'] === null) {
            $evaluation = [
                'moyenne' => 0,
                'total' => 0
            ];
        }

        echo $this->twig->render('entreprise.html.twig', [
            'entreprise' => $entreprise,
            'offres' => $offres,
            'evaluation' => $evaluation
        ]);
    }

    // =========================
    // 📋 LISTE ENTREPRISES
    // =========================
    public function liste()
    {
        $page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
        if ($page < 1) $page = 1;

        $limit = 6;
        $offset = ($page - 1) * $limit;

        $nom = $_GET['nom'] ?? '';
        $domaine = $_GET['domaine'] ?? '';
        $ville = $_GET['ville'] ?? '';

        $conditions = [];
        $params = [];

        if (!empty($nom)) {
            $conditions[] = "e.nom_entreprise LIKE :nom";
            $params[':nom'] = "%$nom%";
        }

        if (!empty($domaine) && $domaine !== "Tous les domaines") {
            $conditions[] = "e.domaine = :domaine";
            $params[':domaine'] = $domaine;
        }

        if (!empty($ville) && $ville !== "Toutes les villes") {
            $conditions[] = "e.ville = :ville";
            $params[':ville'] = $ville;
        }

        $where = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : "";

        $sql = "
            SELECT 
                e.*, 
                COUNT(DISTINCT o.id_offre) as nb_offres,
                ROUND(AVG(ev.note),1) as moyenne
            FROM entreprise e
            LEFT JOIN offre o ON e.id_entreprise = o.id_entreprise
            LEFT JOIN evaluation ev ON e.id_entreprise = ev.id_entreprise
            $where
            GROUP BY e.id_entreprise
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $entreprises = $stmt->fetchAll();

        $sqlCount = "
            SELECT COUNT(DISTINCT e.id_entreprise)
            FROM entreprise e
            LEFT JOIN offre o ON e.id_entreprise = o.id_entreprise
            $where
        ";

        $stmtCount = $this->pdo->prepare($sqlCount);

        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }

        $stmtCount->execute();
        $total = $stmtCount->fetchColumn();

        $totalPages = max(1, ceil($total / $limit));

        $domaines = $this->pdo->query("SELECT DISTINCT domaine FROM entreprise")->fetchAll(PDO::FETCH_COLUMN);
        $villes = $this->pdo->query("SELECT DISTINCT ville FROM entreprise")->fetchAll(PDO::FETCH_COLUMN);

        echo $this->twig->render('liste-entreprises.html.twig', [
            'entreprises' => $entreprises,
            'page' => $page,
            'totalPages' => $totalPages,
            'nombre_entreprises' => $total,
            'domaines' => $domaines,
            'villes' => $villes,
            'filtres' => [
                'nom' => $nom,
                'domaine' => $domaine,
                'ville' => $ville
            ]
        ]);
    }

    // =========================
    // 🏢 GESTION ENTREPRISES
    // =========================
    public function gestion(): void
    {
        $this->verifierAccesAdminOuPilote();

        $stmt = $this->pdo->query("
            SELECT id_entreprise, nom_entreprise, domaine, ville, email
            FROM entreprise
            ORDER BY nom_entreprise ASC
        ");

        $entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo $this->twig->render('gestion-entreprises.html.twig', [
            'entreprises' => $entreprises
        ]);
    }

    // =========================
    // ➕ CREER ENTREPRISE
    // =========================
    public function creer(): void
    {
        $this->verifierAccesAdminOuPilote();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nomEntreprise = trim($_POST['nom_entreprise'] ?? '');
            $domaine = trim($_POST['domaine'] ?? '');
            $ville = trim($_POST['ville'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');

            if (
                empty($nomEntreprise) ||
                empty($domaine) ||
                empty($ville) ||
                empty($description) ||
                empty($email) ||
                empty($telephone)
            ) {
                echo $this->twig->render('creer-entreprise.html.twig', [
                    'mode' => 'creer',
                    'erreur' => 'Tous les champs sont obligatoires.',
                    'entreprise' => [
                        'nom_entreprise' => $nomEntreprise,
                        'domaine' => $domaine,
                        'ville' => $ville,
                        'description' => $description,
                        'email' => $email,
                        'telephone' => $telephone
                    ]
                ]);
                return;
            }

            $sql = "
                INSERT INTO entreprise (nom_entreprise, domaine, ville, description, email, telephone)
                VALUES (:nom_entreprise, :domaine, :ville, :description, :email, :telephone)
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nom_entreprise' => $nomEntreprise,
                ':domaine' => $domaine,
                ':ville' => $ville,
                ':description' => $description,
                ':email' => $email,
                ':telephone' => $telephone
            ]);

            header('Location: index.php?page=entreprises&success=creation');
            exit;
        }

        echo $this->twig->render('creer-entreprise.html.twig', [
            'mode' => 'creer',
            'entreprise' => null
        ]);
    }

    // =========================
    // ✏️ MODIFIER ENTREPRISE
    // =========================
    public function modifier(): void
    {
        $this->verifierAccesAdminOuPilote();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=entreprises');
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM entreprise WHERE id_entreprise = ?");
        $stmt->execute([$id]);
        $entreprise = $stmt->fetch();

        if (!$entreprise) {
            header('Location: index.php?page=entreprises');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nomEntreprise = trim($_POST['nom_entreprise'] ?? '');
            $domaine = trim($_POST['domaine'] ?? '');
            $ville = trim($_POST['ville'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');

            if (
                empty($nomEntreprise) ||
                empty($domaine) ||
                empty($ville) ||
                empty($description) ||
                empty($email) ||
                empty($telephone)
            ) {
                echo $this->twig->render('modifier-entreprise.html.twig', [
                    'mode' => 'modifier',
                    'erreur' => 'Tous les champs sont obligatoires.',
                    'entreprise' => [
                        'id_entreprise' => $id,
                        'nom_entreprise' => $nomEntreprise,
                        'domaine' => $domaine,
                        'ville' => $ville,
                        'description' => $description,
                        'email' => $email,
                        'telephone' => $telephone
                    ]
                ]);
                return;
            }

            $sql = "
                UPDATE entreprise
                SET nom_entreprise = :nom_entreprise,
                    domaine = :domaine,
                    ville = :ville,
                    description = :description,
                    email = :email,
                    telephone = :telephone
                WHERE id_entreprise = :id
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nom_entreprise' => $nomEntreprise,
                ':domaine' => $domaine,
                ':ville' => $ville,
                ':description' => $description,
                ':email' => $email,
                ':telephone' => $telephone,
                ':id' => $id
            ]);

            header('Location: index.php?page=entreprise&id=' . $id . '&success=modification');
            exit;
        }

        echo $this->twig->render('modifier-entreprise.html.twig', [
            'mode' => 'modifier',
            'entreprise' => $entreprise
        ]);
    }

    // =========================
    // 🗑️ SUPPRIMER ENTREPRISE
    // =========================
    public function supprimer(): void
    {
        $this->verifierAccesAdminOuPilote();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=entreprises');
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM entreprise WHERE id_entreprise = ?");
        $stmt->execute([$id]);
        $entreprise = $stmt->fetch();

        if (!$entreprise) {
            header('Location: index.php?page=entreprises');
            exit;
        }

        $stmt = $this->pdo->prepare("DELETE FROM entreprise WHERE id_entreprise = ?");
        $stmt->execute([$id]);

        header('Location: index.php?page=entreprises&success=suppression');
        exit;
    }

    // =========================
    // ⭐ FORMULAIRE EVALUATION
    // =========================
    public function evaluerForm(): void
    {
        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=entreprises');
            exit;
        }

        $stmt = $this->pdo->prepare("
            SELECT id_entreprise, nom_entreprise
            FROM entreprise
            WHERE id_entreprise = ?
        ");
        $stmt->execute([$id]);
        $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entreprise) {
            header('Location: index.php?page=entreprises');
            exit;
        }

        echo $this->twig->render('evaluer-entreprise.html.twig', [
            'entreprise' => $entreprise
        ]);
    }

    // =========================
    // ⭐ ENVOI EVALUATION
    // =========================
    public function evaluerSubmit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
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
        $id_user = (int) $_SESSION['user']['id'];

        if ($note < 1 || $note > 5 || $id_entreprise <= 0) {
            header('Location: index.php?page=entreprise&id=' . $id_entreprise);
            exit;
        }

        $check = $this->pdo->prepare("
            SELECT * FROM evaluation 
            WHERE id_user = ? AND id_entreprise = ?
        ");
        $check->execute([$id_user, $id_entreprise]);

        if ($check->fetch()) {
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
}