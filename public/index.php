<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/twig.php';
require_once __DIR__ . '/../src/Controller/HomeController.php';
require_once __DIR__ . '/../src/Controller/OffreController.php';
require_once __DIR__ . '/../src/Controller/CandidatureControlleur.php';
require_once __DIR__ . '/../src/Controller/EntrepriseController.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';
require_once __DIR__ . '/../src/Controller/WishlistController.php';

$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'offres':
        $controller = new OffreController($pdo, $twig);
        $controller->liste();
        break;

    case 'offre':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $controller = new OffreController($pdo, $twig);
        $controller->detail($id);
        break;

    case 'entreprises':
        $controller = new EntrepriseController($pdo, $twig);
        $controller->liste();
        break;

    case 'entreprise':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $controller = new EntrepriseController($pdo, $twig);
        $controller->detail($id);
        break;

    case 'postuler':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $controller = new CandidatureController($pdo, $twig);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->submit($id);
        } else {
            $controller->form($id);
        }
        break;

    case 'home':
    default:
        $controller = new HomeController($pdo, $twig);
        $controller->index();
        break;
    
    case 'menu':
        echo $twig->render('menu.html.twig');
        break;

    case 'mentions':
        echo $twig->render('mentions.html.twig');
        break;

    case 'connexion':
        $controller = new AuthController($pdo, $twig);
        $controller->connexion();
        break;

    case 'inscription':
        $controller = new AuthController($pdo, $twig);
        $controller->inscription();
        break;

    case 'oubli':
        $controller = new AuthController($pdo, $twig);
        $controller->oubli();
        break;

    case 'nouveau-mdp':
        $controller = new AuthController($pdo, $twig);
        $controller->nouveauMdp();
        break;

    case 'conditions':
        echo $twig->render('conditions.html.twig');
        break;

    case 'politique':
        echo $twig->render('politique.html.twig');
        break;

    case 'logout':
        session_destroy();
        header('Location: index.php');
        exit;

    case 'wishlist':
        $controller = new WishlistController($pdo, $twig);
        $controller->liste();
        break;

    case 'wishlist-ajouter':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $controller = new WishlistController($pdo, $twig);
        $controller->ajouter($id);
        break;

    case 'wishlist-supprimer':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $controller = new WishlistController($pdo, $twig);
        $controller->supprimer($id);
        break;
}