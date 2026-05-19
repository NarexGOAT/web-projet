<?php

class CompteController
{
    private $pdo;
    private $twig;

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    public function afficher(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT id_user, nom, prenom, email FROM utilisateur WHERE id_user = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        echo $this->twig->render('compte.html.twig', [
            'utilisateur' => $utilisateur
        ]);
    }

    public function modifier(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=compte');
            exit;
        }

        $id    = (int) $_SESSION['user']['id'];
        $nom   = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mdp   = $_POST['mot_de_passe'] ?? '';
        $mdpConf = $_POST['confirmation_mot_de_passe'] ?? '';

        $erreurs = [];

        if ($nom === '') $erreurs[] = 'Le nom est obligatoire.';
        if ($prenom === '') $erreurs[] = 'Le prénom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';

        if ($mdp !== '' && $mdp !== $mdpConf) {
            $erreurs[] = 'Les mots de passe ne correspondent pas.';
        }

        if (!empty($erreurs)) {
            $utilisateur = ['id_user' => $id, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email];
            echo $this->twig->render('compte.html.twig', ['utilisateur' => $utilisateur, 'erreurs' => $erreurs]);
            return;
        }

        if ($mdp !== '') {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET nom=?, prenom=?, email=?, mot_de_passe=? WHERE id_user=?");
            $stmt->execute([$nom, $prenom, $email, $hash, $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET nom=?, prenom=?, email=? WHERE id_user=?");
            $stmt->execute([$nom, $prenom, $email, $id]);
        }

        $_SESSION['user']['nom']    = $nom;
        $_SESSION['user']['prenom'] = $prenom;
        $_SESSION['user']['email']  = $email;

        header('Location: index.php?page=compte&success=1');
        exit;
    }

    public function supprimer(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $id = (int) $_SESSION['user']['id'];

        $this->pdo->prepare("DELETE FROM candidature WHERE id_user = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM wishlist WHERE id_user = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM evaluation WHERE id_user = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM utilisateur WHERE id_user = ?")->execute([$id]);

        session_destroy();
        header('Location: index.php');
        exit;
    }
}
