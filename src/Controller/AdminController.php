<?php

class AdminController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;
    private UtilisateurModel $utilisateurModel;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo              = $pdo;
        $this->twig             = $twig;
        $this->utilisateurModel = new UtilisateurModel($pdo);
    }

    // =========================
    // 🎓 GESTION ETUDIANTS
    // =========================

    public function gestionEtudiants(): void
    {
        $recherche = $_GET['recherche'] ?? '';
        $etudiants = $this->utilisateurModel->listerParRole(UtilisateurModel::ROLE_ETUDIANT, $recherche);

        echo $this->twig->render('gestion-etudiants.html.twig', [
            'etudiants' => $etudiants,
            'recherche' => $recherche,
        ]);
    }

    public function supprimerEtudiant(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->utilisateurModel->supprimer($id, UtilisateurModel::ROLE_ETUDIANT);
        }
        header('Location: index.php?page=gestion-etudiants');
        exit;
    }

    public function formModifierEtudiant(int $id): void
    {
        $etudiant = $this->utilisateurModel->findByIdEtRole($id, UtilisateurModel::ROLE_ETUDIANT);
        if (!$etudiant) die("Étudiant introuvable");

        echo $this->twig->render('modifier-etudiant.html.twig', ['etudiant' => $etudiant]);
    }

    public function updateEtudiant(int $id): void
    {
        $this->utilisateurModel->modifier(
            $id, UtilisateurModel::ROLE_ETUDIANT,
            $_POST['nom'] ?? '', $_POST['prenom'] ?? '', $_POST['email'] ?? ''
        );
        header('Location: index.php?page=gestion-etudiants');
        exit;
    }

    // =========================
    // 👨‍✈️ GESTION PILOTES
    // =========================

    public function gestionPilotes(): void
    {
        $recherche = $_GET['recherche'] ?? '';
        $pilotes   = $this->utilisateurModel->listerParRole(UtilisateurModel::ROLE_RECRUTEUR, $recherche);

        echo $this->twig->render('gestion-pilotes.html.twig', [
            'pilotes'   => $pilotes,
            'recherche' => $recherche,
        ]);
    }

    public function supprimerPilote(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->utilisateurModel->supprimer($id, UtilisateurModel::ROLE_RECRUTEUR);
        }
        header('Location: index.php?page=gestion-pilotes');
        exit;
    }

    public function formModifierPilote(int $id): void
    {
        $pilote = $this->utilisateurModel->findByIdEtRole($id, UtilisateurModel::ROLE_RECRUTEUR);
        if (!$pilote) die("Pilote introuvable");

        echo $this->twig->render('modifier-pilote.html.twig', ['pilote' => $pilote]);
    }

    public function updatePilote(int $id): void
    {
        $this->utilisateurModel->modifier(
            $id, UtilisateurModel::ROLE_RECRUTEUR,
            $_POST['nom'] ?? '', $_POST['prenom'] ?? '', $_POST['email'] ?? ''
        );
        header('Location: index.php?page=gestion-pilotes');
        exit;
    }
}