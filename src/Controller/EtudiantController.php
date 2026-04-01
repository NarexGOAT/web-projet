<?php

class EtudiantController
{
    private $pdo;
    private $twig;

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    public function gestion()
    {
        //  récupérer les étudiants
        $stmt = $this->pdo->query("SELECT * FROM etudiant");
        $etudiants = $stmt->fetchAll();

        //  afficher la page Twig
        echo $this->twig->render('gestion-etudiants.html.twig', [
            'etudiants' => $etudiants,
            'session' => $_SESSION
        ]);
    }
}