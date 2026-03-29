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
        header('Location: index.php?page=offre&id=' . $idOffre);
        exit;
    }
}