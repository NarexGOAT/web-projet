<?php

class HomeController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    public function index(): void
    {
        $sqlCountOffres = "SELECT COUNT(*) FROM offre";
        $total_offres = (int) $this->pdo->query($sqlCountOffres)->fetchColumn();

        $sqlCountEntreprises = "SELECT COUNT(*) FROM entreprise";
        $total_entreprises = (int) $this->pdo->query($sqlCountEntreprises)->fetchColumn();

        $sqlDernieres = "
            SELECT 
                o.*,
                e.nom_entreprise
            FROM offre o
            LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            ORDER BY o.date_publication DESC
            LIMIT 3
        ";
        $dernieres_offres = $this->pdo->query($sqlDernieres)->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('index.html.twig', [
            'total_offres'      => $total_offres,
            'total_entreprises' => $total_entreprises,
            'dernieres_offres'  => $dernieres_offres,
            'wishlist_added'    => !empty($_GET['wishlist_added']),
            'wishlist_exists'   => !empty($_GET['wishlist_exists']),
        ]);
    }
}