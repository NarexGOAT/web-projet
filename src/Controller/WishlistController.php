<?php

class WishlistController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
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
            o.*,
            e.nom_entreprise,
            e.ville,
            w.date_ajout
        FROM wishlist w
        JOIN offre o ON o.id_offre = w.id_offre
        LEFT JOIN entreprise e ON e.id_entreprise = o.id_entreprise
        WHERE w.id_user = :id_user
        ORDER BY w.date_ajout DESC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id_user' => $idUser]);
    $offres = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $sqlCandid = "
        SELECT id_offre
        FROM candidature
        WHERE id_user = :id_user
    ";
    $stmtCandid = $this->pdo->prepare($sqlCandid);
    $stmtCandid->execute(['id_user' => $idUser]);
    $idsCandidatures = $stmtCandid->fetchAll(\PDO::FETCH_COLUMN);

    echo $this->twig->render('wishlist.html.twig', [
        'offres'          => $offres,
        'idsCandidatures' => $idsCandidatures,
    ]);
}

    public function ajouter(int $idOffre): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $idUser = (int) $_SESSION['user_id'];

        $sqlCheck = "
            SELECT 1 FROM wishlist
            WHERE id_user = :id_user AND id_offre = :id_offre
        ";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([
            'id_user'  => $idUser,
            'id_offre' => $idOffre,
        ]);

        if (!$stmtCheck->fetch()) {
            $sqlInsert = "
                INSERT INTO wishlist (id_user, id_offre)
                VALUES (:id_user, :id_offre)
            ";
            $stmtInsert = $this->pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                'id_user'  => $idUser,
                'id_offre' => $idOffre,
            ]);
        }

        header('Location: index.php?page=offre&id=' . $idOffre);
        exit;
    }

    public function supprimer(int $idOffre): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $idUser = (int) $_SESSION['user_id'];

        $sqlDelete = "
            DELETE FROM wishlist
            WHERE id_user = :id_user AND id_offre = :id_offre
        ";
        $stmtDelete = $this->pdo->prepare($sqlDelete);
        $stmtDelete->execute([
            'id_user'  => $idUser,
            'id_offre' => $idOffre,
        ]);

        header('Location: index.php?page=wishlist');
        exit;
    }
}