<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ================= ROLE CHECK =================
function requireRole(array $roles)
{
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?page=connexion');
        exit;
    }

    if (!in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);
        echo "⛔ Accès interdit";
        exit;
    }
}

// ================= CONFIG =================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/twig.php';

// session dispo dans Twig
$twig->addGlobal('session', $_SESSION);

// ================= CONTROLLERS =================
require_once __DIR__ . '/../src/Controller/HomeController.php';
require_once __DIR__ . '/../src/Controller/OffreController.php';
require_once __DIR__ . '/../src/Controller/EntrepriseController.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';
require_once __DIR__ . '/../src/Controller/CandidatureController.php';
require_once __DIR__ . '/../src/Controller/WishlistController.php';
require_once __DIR__ . '/../src/Controller/MesCandidaturesController.php';
require_once __DIR__ . '/../src/Controller/AdminController.php';

// ================= VARIABLES =================
$page = $_GET['page'] ?? 'home';

// ================= ROUTER =================
switch ($page) {

    // ================= ACCUEIL =================
    case 'home':
        (new HomeController($pdo, $twig))->index();
        break;

    // ================= AUTH =================
    case 'connexion':
        (new AuthController($pdo, $twig))->connexion();
        break;

    case 'inscription':
        requireRole(['admin', 'recruteur']);
        (new AuthController($pdo, $twig))->inscription();
        break;

    case 'logout':
        session_destroy();
        header('Location: index.php');
        exit;

    // ================= OFFRES =================
    case 'offres':
        (new OffreController($pdo, $twig))->liste();
        break;

    case 'offre':
        $id = (int) ($_GET['id'] ?? 0);
        (new OffreController($pdo, $twig))->detail($id);
        break;

    case 'gestion-offres':
        requireRole(['admin', 'recruteur']);
        (new OffreController($pdo, $twig))->gestion();
        break;

    case 'offre-creer':
        requireRole(['admin', 'recruteur']);
        (new OffreController($pdo, $twig))->creer();
        break;

    case 'offre-modifier':
        requireRole(['admin', 'recruteur']);
        (new OffreController($pdo, $twig))->modifier();
        break;

    case 'offre-supprimer':
        requireRole(['admin', 'recruteur']);
        (new OffreController($pdo, $twig))->supprimer();
        break;

    // ================= ENTREPRISES =================
    case 'entreprises':
        (new EntrepriseController($pdo, $twig))->liste();
        break;

    case 'entreprise':
        $id = (int) ($_GET['id'] ?? 0);
        (new EntrepriseController($pdo, $twig))->detail($id);
        break;

    case 'gestion-entreprises':
        requireRole(['admin', 'recruteur']);
        (new EntrepriseController($pdo, $twig))->gestion();
        break;

    case 'entreprise-creer':
        requireRole(['admin', 'recruteur']);
        (new EntrepriseController($pdo, $twig))->creer();
        break;

    case 'entreprise-modifier':
        requireRole(['admin', 'recruteur']);
        (new EntrepriseController($pdo, $twig))->modifier();
        break;

    case 'entreprise-supprimer':
        requireRole(['admin', 'recruteur']);
        (new EntrepriseController($pdo, $twig))->supprimer();
        break;
        
    case 'entreprise-evaluer':
        requireRole(['etudiant']);
        (new EntrepriseController($pdo, $twig))->evaluerForm();
        break;

    case 'entreprise-evaluer-submit':
        requireRole(['etudiant']);
        (new EntrepriseController($pdo, $twig))->evaluerSubmit();
        break;

    // ================= CANDIDATURE =================
    case 'postuler':
        requireRole(['etudiant']);

        $id = (int) ($_GET['id'] ?? 0);
        $controller = new CandidatureController($pdo, $twig);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->submit($id);
        } else {
            $controller->form($id);
        }
        break;

    // ================= WISHLIST =================
    case 'wishlist':
        requireRole(['etudiant']);
        (new WishlistController($pdo, $twig))->liste();
        break;
        
    case 'wishlist-ajouter':
        requireRole(['etudiant']);
        $id = (int) ($_GET['id'] ?? 0);
        (new WishlistController($pdo, $twig))->ajouter($id);
        break;

    case 'wishlist-supprimer':
        requireRole(['etudiant']);
        $id = (int) ($_GET['id'] ?? 0);
        (new WishlistController($pdo, $twig))->supprimer($id);
        break;

    // ================= MES CANDIDATURES =================
    case 'mes-candidatures':
        requireRole(['etudiant']);
        (new MesCandidaturesController($pdo, $twig))->liste();
        break;

    // ================= ADMIN =================

    // 🎓 étudiants
    case 'gestion-etudiants':
        requireRole(['admin', 'recruteur']);
        (new AdminController($pdo, $twig))->gestionEtudiants();
        break;

    case 'supprimer-etudiant':
        requireRole(['admin','recruteur']);
        (new AdminController($pdo, $twig))->supprimerEtudiant();
        break;

    case 'modifier-etudiant':
        requireRole(['admin','recruteur']);

        $controller = new AdminController($pdo, $twig);
        $id = (int) ($_GET['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updateEtudiant($id);
        } else {
            $controller->formModifierEtudiant($id);
        }
        break;

    // 👨‍✈️ pilotes (recruteurs)
    case 'gestion-pilotes':
        requireRole(['admin']);
        (new AdminController($pdo, $twig))->gestionPilotes();
        break;

    case 'supprimer-pilote':
        requireRole(['admin']);
        (new AdminController($pdo, $twig))->supprimerPilote();
        break;

    case 'modifier-pilote':
        requireRole(['admin']);

        $controller = new AdminController($pdo, $twig);
        $id = (int) ($_GET['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updatePilote($id);
        } else {
            $controller->formModifierPilote($id);
        }
        break;

    // ================= PAGES STATIQUES =================
    case 'mentions':
        echo $twig->render('mentions.html.twig');
        break;

    case 'conditions':
        echo $twig->render('conditions.html.twig');
        break;

    case 'politique':
        echo $twig->render('politique.html.twig');
        break;

    // ================= DEFAULT =================
    default:
        (new HomeController($pdo, $twig))->index();
        break;
}