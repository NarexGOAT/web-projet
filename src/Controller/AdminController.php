<?php

class AdminController
{
    private $pdo;
    private $twig;

    // 🎯 constantes propres (important)
    private const ROLE_ETUDIANT = 1;
    private const ROLE_RECRUTEUR = 2; // = pilotes
    private const ROLE_ADMIN = 3;

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    // =========================
    // 🎓 GESTION ETUDIANTS
    // =========================
    public function gestionEtudiants()
    {
        $recherche = $_GET['recherche'] ?? '';

        $sql = "
            SELECT id_user, nom, prenom, email
            FROM utilisateur
            WHERE id_role = :role
        ";

        $params = [
            ':role' => self::ROLE_ETUDIANT
        ];

        if (!empty($recherche)) {
            $sql .= " AND (nom LIKE :recherche OR prenom LIKE :recherche)";
            $params[':recherche'] = "%$recherche%";
        }

        $sql .= " ORDER BY nom ASC";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $etudiants = $stmt->fetchAll();

        echo $this->twig->render('gestion-etudiants.html.twig', [
            'etudiants' => $etudiants,
            'recherche' => $recherche
        ]);
    }

    // =========================
    // ❌ SUPPRIMER ETUDIANT
    // =========================
    public function supprimerEtudiant()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $stmt = $this->pdo->prepare("
                DELETE FROM utilisateur
                WHERE id_user = :id AND id_role = :role
            ");

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':role', self::ROLE_ETUDIANT, PDO::PARAM_INT);
            $stmt->execute();
        }

        header('Location: index.php?page=gestion-etudiants');
        exit;
    }

    // =========================
    // ✏️ FORM MODIFIER ETUDIANT
    // =========================
    public function formModifierEtudiant($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id_user, nom, prenom, email
            FROM utilisateur
            WHERE id_user = :id AND id_role = :role
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':role', self::ROLE_ETUDIANT, PDO::PARAM_INT);
        $stmt->execute();

        $etudiant = $stmt->fetch();

        if (!$etudiant) {
            die("Étudiant introuvable");
        }

        echo $this->twig->render('modifier-etudiant.html.twig', [
            'etudiant' => $etudiant
        ]);
    }

    // =========================
    // 💾 UPDATE ETUDIANT
    // =========================
    public function updateEtudiant($id)
    {
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $this->pdo->prepare("
            UPDATE utilisateur
            SET nom = :nom, prenom = :prenom, email = :email
            WHERE id_user = :id AND id_role = :role
        ");

        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':prenom', $prenom);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':role', self::ROLE_ETUDIANT, PDO::PARAM_INT);

        $stmt->execute();

        header('Location: index.php?page=gestion-etudiants');
        exit;
    }

    // =========================
    // 👨‍✈️ GESTION PILOTES
    // =========================
    public function gestionPilotes()
    {
        $recherche = $_GET['recherche'] ?? '';

        $sql = "
            SELECT id_user, nom, prenom, email
            FROM utilisateur
            WHERE id_role = :role
        ";

        $params = [
            ':role' => self::ROLE_RECRUTEUR // ✅ corrigé ici
        ];

        if (!empty($recherche)) {
            $sql .= " AND (nom LIKE :recherche OR prenom LIKE :recherche)";
            $params[':recherche'] = "%$recherche%";
        }

        $sql .= " ORDER BY nom ASC";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $pilotes = $stmt->fetchAll();

        echo $this->twig->render('gestion-pilotes.html.twig', [
            'pilotes' => $pilotes,
            'recherche' => $recherche
        ]);
    }

    // =========================
    // ❌ SUPPRIMER PILOTE
    // =========================
    public function supprimerPilote()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $stmt = $this->pdo->prepare("
                DELETE FROM utilisateur
                WHERE id_user = :id AND id_role = :role
            ");

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':role', self::ROLE_RECRUTEUR, PDO::PARAM_INT);
            $stmt->execute();
        }

        header('Location: index.php?page=gestion-pilotes');
        exit;
    }

    // =========================
    // ✏️ FORM MODIFIER PILOTE
    // =========================
    public function formModifierPilote($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id_user, nom, prenom, email
            FROM utilisateur
            WHERE id_user = :id AND id_role = :role
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':role', self::ROLE_RECRUTEUR, PDO::PARAM_INT);
        $stmt->execute();

        $pilote = $stmt->fetch();

        if (!$pilote) {
            die("Pilote introuvable");
        }

        echo $this->twig->render('modifier-pilote.html.twig', [
            'pilote' => $pilote
        ]);
    }

    // =========================
    // 💾 UPDATE PILOTE
    // =========================
    public function updatePilote($id)
    {
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $this->pdo->prepare("
            UPDATE utilisateur
            SET nom = :nom, prenom = :prenom, email = :email
            WHERE id_user = :id AND id_role = :role
        ");

        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':prenom', $prenom);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':role', self::ROLE_RECRUTEUR, PDO::PARAM_INT);

        $stmt->execute();

        header('Location: index.php?page=gestion-pilotes');
        exit;
    }
}