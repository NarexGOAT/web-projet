<?php

class AuthController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    public function connexion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Location: index.php');
            exit;
        }

        echo $this->twig->render('connexion.html.twig');
    }

    public function inscription(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom        = trim($_POST['nom'] ?? '');
        $prenom     = trim($_POST['prenom'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $mdp        = $_POST['mot_de_passe'] ?? '';
        $mdpConf    = $_POST['mot_de_passe_confirm'] ?? '';
        $conditions = isset($_POST['conditions']);
        $idRole     = isset($_POST['id_role']) ? (int) $_POST['id_role'] : 1;

        $erreurs = [];

        if ($nom === '') {
            $erreurs[] = 'Le nom est obligatoire.';
        }
        if ($prenom === '') {
            $erreurs[] = 'Le prénom est obligatoire.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Email invalide.';
        }
        if ($mdp === '' || $mdpConf === '') {
            $erreurs[] = 'Le mot de passe et sa confirmation sont obligatoires.';
        } elseif ($mdp !== $mdpConf) {
            $erreurs[] = 'Les mots de passe ne correspondent pas.';
        }
        if (!$conditions) {
            $erreurs[] = 'Vous devez accepter les conditions d’utilisation.';
        }
        if (!in_array($idRole, [1, 2, 3], true)) {
            $erreurs[] = 'Rôle invalide.';
        }

        // Vérifier si email déjà utilisé
        if (empty($erreurs)) {
            $sqlCheck = "SELECT id_user FROM utilisateur WHERE email = :email";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute(['email' => $email]);
            if ($stmtCheck->fetch()) {
                $erreurs[] = 'Cet email est déjà utilisé.';
            }
        }

        if (!empty($erreurs)) {
            echo $this->twig->render('inscription.html.twig', [
                'erreurs' => $erreurs,
                'old' => [
                    'nom'     => $nom,
                    'prenom'  => $prenom,
                    'email'   => $email,
                    'id_role' => $idRole,
                ],
            ]);
            return;
        }

        $hash = password_hash($mdp, PASSWORD_DEFAULT);

        $sqlInsert = "
            INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, id_role)
            VALUES (:nom, :prenom, :email, :mot_de_passe, :id_role)
        ";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'nom'          => $nom,
            'prenom'       => $prenom,
            'email'        => $email,
            'mot_de_passe' => $hash,
            'id_role'      => $idRole,
        ]);

        header('Location: index.php?page=connexion');
        exit;
    }

    echo $this->twig->render('inscription.html.twig');
}

    public function oubli(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Location: index.php?page=nouveau-mdp');
        exit;
    }

    echo $this->twig->render('oubli.html.twig');
}
    public function nouveauMdp(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Location: index.php?page=connexion');
        exit;
    }

    echo $this->twig->render('nouveau-mdp.html.twig');
}
}