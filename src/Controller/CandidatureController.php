<?php

class CandidatureController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    public function form(int $idOffre): void
    {
        $sql = '
            SELECT o.*, e.nom_entreprise, e.ville
            FROM offre o
            LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            WHERE o.id_offre = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idOffre]);
        $offre = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$offre) {
            http_response_code(404);
            echo 'Offre introuvable';
            return;
        }

        echo $this->twig->render('postuler.html.twig', [
            'offre' => $offre,
        ]);
    }

    public function submit(int $idOffre): void
    {
        // 1. CORRECTION DE LA SESSION
        if (empty($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $idUser = (int) $_SESSION['user']['id'];

        // 2. CORRECTION DES CHAMPS (On garde uniquement ce qui est dans ton HTML)
        $nom   = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $erreurs = [];

        if ($nom === '')      $erreurs[] = 'Le nom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Email invalide.';
        }

        if (!empty($erreurs)) {
            $sql = '
                SELECT o.*, e.nom_entreprise, e.ville
                FROM offre o
                LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
                WHERE o.id_offre = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $idOffre]);
            $offre = $stmt->fetch(\PDO::FETCH_ASSOC);

            echo $this->twig->render('postuler.html.twig', [
                'offre'   => $offre,
                'erreurs' => $erreurs,
                'old'     => [
                    'nom'   => $nom,
                    'email' => $email,
                ],
            ]);
            return;
        }

        // On gère les noms de fichiers (ton HTML utilise name="lettre" et pas "lm")
        $cvNom = !empty($_FILES['cv']['name']) ? $_FILES['cv']['name'] : 'CV non fourni';
        $lmNom = !empty($_FILES['lettre']['name']) ? $_FILES['lettre']['name'] : 'LM non fournie';

        // On vérifie si l'étudiant a déjà postulé
        $sqlCheck = "
            SELECT id_candidature 
            FROM candidature
            WHERE id_user = :id_user AND id_offre = :id_offre
        ";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([
            'id_user'  => $idUser,
            'id_offre' => $idOffre,
        ]);
        $deja = $stmtCheck->fetch();

        // 3. CORRECTION DES REDIRECTIONS
        if ($deja) {
            header('Location: index.php?page=mes-candidatures');
            exit;
        }

        // 4. CORRECTION DES VARIABLES FANTÔMES
        $sqlInsert = "
            INSERT INTO candidature (cv, lm, id_user, id_offre)
            VALUES (:cv, :lm, :id_user, :id_offre)
        ";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'cv'       => $cvNom,
            'lm'       => $lmNom,
            'id_user'  => $idUser,
            'id_offre' => $idOffre,
        ]);

        header('Location: index.php?page=mes-candidatures');
        exit;
    }

public function liste(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=connexion');
        exit;
    }

    $idUser = (int) $_SESSION['user_id'];

    $sql = "
        SELECT 
            c.*,
            o.titre,
            o.duree_stage,
            o.competence,
            e.nom_entreprise,
            e.ville
        FROM candidature c
        JOIN offre o ON o.id_offre = c.id_offre
        LEFT JOIN entreprise e ON e.id_entreprise = o.id_entreprise
        WHERE c.id_user = :id_user
        ORDER BY c.date_envoi DESC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id_user' => $idUser]);
    $candidatures = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo $this->twig->render('candidatures.html.twig', [
        'candidatures' => $candidatures,
    ]);
}
}