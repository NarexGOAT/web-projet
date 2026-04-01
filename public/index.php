<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/twig.php';
require_once __DIR__ . '/../src/Controller/HomeController.php';
require_once __DIR__ . '/../src/Controller/OffreController.php';
require_once __DIR__ . '/../src/Controller/CandidatureController.php';
require_once __DIR__ . '/../src/Controller/EntrepriseController.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';
require_once __DIR__ . '/../src/Controller/WishlistController.php';
require_once __DIR__ . '/../src/Controller/CandidatureController.php';

$page = $_GET['page'] ?? 'home';

$entrepriseController = new EntrepriseController($pdo, $twig);
$offreController = new OffreController($pdo, $twig);

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

    case 'offre-creer':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $offreController->creerSubmit();
        } else {
            $offreController->creerForm();
        }
        break;

    case 'offre-modifier':
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $offreController->modifierSubmit($id);
    } else {
        $offreController->modifierForm($id);
    }
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

    case 'entreprise-creer':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entrepriseController->creerSubmit();
        } else {
            $entrepriseController->creerForm();
        }
        break;

    case 'entreprise-modifier':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entrepriseController->modifierSubmit($id);
        } else {
            $entrepriseController->modifierForm($id);
        }
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

    case 'candidatures':
        $controller = new CandidatureController($pdo, $twig);
        $controller->liste();
        break;
}