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