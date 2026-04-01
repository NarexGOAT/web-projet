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

    // =========================
    // 🔐 CONNEXION
    // =========================
    public function connexion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = trim($_POST['email'] ?? '');
            $mdp   = $_POST['mot_de_passe'] ?? '';

            $erreurs = [];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = 'Email invalide.';
            }

            if ($mdp === '') {
                $erreurs[] = 'Le mot de passe est obligatoire.';
            }

            if (empty($erreurs)) {

                $sql = "
                    SELECT u.*, r.role
                    FROM utilisateur u
                    JOIN role r ON u.id_role = r.id_role
                    WHERE u.email = :email
                ";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
                    $erreurs[] = 'Identifiants incorrects.';
                } else {

                    $roleLabel = match ($user['role']) {
                        'recruteur' => 'Pilote',
                        'admin'     => 'Admin',
                        'etudiant'  => 'Étudiant',
                        default     => ucfirst($user['role'])
                    };

                    $_SESSION['user'] = [
                        'id'         => $user['id_user'],
                        'nom'        => $user['nom'],
                        'prenom'     => $user['prenom'],
                        'email'      => $user['email'],
                        'role'       => $user['role'],
                        'role_label' => $roleLabel
                    ];

                    header('Location: index.php');
                    exit;
                }
            }

            echo $this->twig->render('connexion.html.twig', [
                'erreurs' => $erreurs,
                'old' => ['email' => $email],
            ]);
            return;
        }

        echo $this->twig->render('connexion.html.twig');
    }

    // =========================
    // 📝 INSCRIPTION
    // =========================
    public function inscription(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $nom        = trim($_POST['nom'] ?? '');
            $prenom     = trim($_POST['prenom'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $mdp        = $_POST['mot_de_passe'] ?? '';
            $mdpConf    = $_POST['mot_de_passe_confirm'] ?? '';
            $conditions = isset($_POST['conditions']);
            $redirect   = $_POST['redirect'] ?? 'home';

            // 🎯 ROLE PAR DEFAUT
            $idRole = 1; // étudiant

            // 🔐 SI ADMIN → peut choisir
            if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {

                $roleMap = [
                    'etudiant' => 1,
                    'pilote'   => 2
                ];

                if (isset($_POST['role']) && isset($roleMap[$_POST['role']])) {
                    $idRole = $roleMap[$_POST['role']];
                }
            }

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
                $erreurs[] = 'Le mot de passe est obligatoire.';
            } elseif ($mdp !== $mdpConf) {
                $erreurs[] = 'Les mots de passe ne correspondent pas.';
            }

            if (!$conditions) {
                $erreurs[] = 'Vous devez accepter les conditions.';
            }

            // Vérif email unique
            if (empty($erreurs)) {
                $stmt = $this->pdo->prepare("SELECT id_user FROM utilisateur WHERE email = ?");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $erreurs[] = 'Cet email est déjà utilisé.';
                }
            }

            if (!empty($erreurs)) {
                echo $this->twig->render('inscription.html.twig', [
                    'erreurs' => $erreurs,
                    'old' => [
                        'nom'    => $nom,
                        'prenom' => $prenom,
                        'email'  => $email,
                        'role'   => $_POST['role'] ?? 'etudiant'
                    ],
                    'redirect' => $redirect
                ]);
                return;
            }

            // 🔐 HASH MDP
            $hash = password_hash($mdp, PASSWORD_DEFAULT);

            // 💾 INSERT
            $stmt = $this->pdo->prepare("
                INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, id_role)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $nom,
                $prenom,
                $email,
                $hash,
                $idRole
            ]);

            // 🔁 REDIRECTION INTELLIGENTE
            header("Location: index.php?page=" . $redirect);
            exit;
        }

        // GET
        echo $this->twig->render('inscription.html.twig', [
            'redirect' => $_GET['redirect'] ?? null
        ]);
    }

    // =========================
    // 🔁 OUBLI MDP
    // =========================
    public function oubli(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Location: index.php?page=nouveau-mdp');
            exit;
        }

        echo $this->twig->render('oubli.html.twig');
    }

    // =========================
    // 🔑 NOUVEAU MDP
    // =========================
    public function nouveauMdp(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Location: index.php?page=connexion');
            exit;
        }

        echo $this->twig->render('nouveau-mdp.html.twig');
    }

    // =========================
    // 🚪 DECONNEXION
    // =========================
    public function logout(): void
    {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}