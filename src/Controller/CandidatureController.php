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
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=connexion');
        exit;
    }

    $idUser = (int) $_SESSION['user_id'];

    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');

    $erreurs = [];

    if ($nom === '')      $erreurs[] = 'Le nom est obligatoire.';
    if ($prenom === '')   $erreurs[] = 'Le prénom est obligatoire.';
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
            'offre'  => $offre,
            'erreurs'=> $erreurs,
            'old'    => [
                'nom'       => $nom,
                'prenom'    => $prenom,
                'email'     => $email,
                'telephone' => $telephone,
            ],
        ]);
        return;
    }

        $cvNom = !empty($_FILES['cv']['name']) ? $_FILES['cv']['name'] : 'CV non fourni';
        $lmNom = !empty($_FILES['lm']['name']) ? $_FILES['lm']['name'] : 'LM non fournie';

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

if ($deja) {
    header('Location: index.php?page=candidatures');
    exit;
}

        $sqlInsert = "
    INSERT INTO candidature (cv, lm, id_user, id_offre)
    VALUES (:cv, :lm, :id_user, :id_offre)
";
$stmtInsert = $this->pdo->prepare($sqlInsert);
$stmtInsert->execute([
    'cv'       => $cvSimule, //
    'lm'       => $lmSimule, //
    'id_user'  => $idUser,
    'id_offre' => $idOffre,
]);

        header('Location: index.php?page=offres');
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