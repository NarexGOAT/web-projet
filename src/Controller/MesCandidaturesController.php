<?php

class MesCandidaturesController
{
    private $pdo;
    private $twig;

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    public function liste()
    {
        // sécurité 
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $id_user = $_SESSION['user']['id'];

        // récupérer candidatures + offres + entreprise
        $stmt = $this->pdo->prepare("
            SELECT c.*, o.titre, e.nom_entreprise, e.ville
            FROM candidature c
            JOIN offre o ON c.id_offre = o.id_offre
            JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            WHERE c.id_user = ?
            ORDER BY c.date_envoi DESC
        ");

        $stmt->execute([$id_user]);
        $candidatures = $stmt->fetchAll();

        // rendu Twig
        echo $this->twig->render('mes-candidatures.html.twig', [
            'candidatures' => $candidatures
        ]);
    }
}