<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ================= CONFIG =================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/twig.php';

// ================= ROLE CHECK =================
function requireRole(array $roles)
{
    global $twig;

    if (!isset($_SESSION['user'])) {
        header('Location: index.php?page=connexion');
        exit;
    }

    if (!in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);

        // URL de retour : referer (si même domaine) sinon accueil
        $retour = 'index.php';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $ref  = parse_url($_SERVER['HTTP_REFERER']);
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (!empty($ref['host']) && $ref['host'] === $host) {
                $retour = $_SERVER['HTTP_REFERER'];
            }
        }

        echo $twig->render('acces-refuse.html.twig', ['retour' => $retour]);
        exit;
    }
}

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
require_once __DIR__ . '/../src/Controller/CompteController.php';
require_once __DIR__ . '/../src/Controller/StatistiquesController.php';

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

    case 'oubli':
        (new AuthController($pdo, $twig))->oubli();
        break;

    case 'nouveau-mdp':
        (new AuthController($pdo, $twig))->nouveauMdp();
        break;

    // ================= COMPTE =================
    case 'compte':
        requireRole(['etudiant', 'recruteur', 'admin']);
        (new CompteController($pdo, $twig))->afficher();
        break;

    case 'compte-modifier':
        requireRole(['etudiant', 'recruteur', 'admin']);
        (new CompteController($pdo, $twig))->modifier();
        break;

    case 'compte-supprimer':
        requireRole(['etudiant', 'recruteur', 'admin']);
        (new CompteController($pdo, $twig))->supprimer();
        break;

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
        requireRole(['admin', 'recruteur']);
        (new EntrepriseController($pdo, $twig))->evaluerForm();
        break;

    case 'entreprise-evaluer-submit':
        requireRole(['admin', 'recruteur']);
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

    // SFx22 — candidatures des étudiants vues par le pilote
    case 'candidatures-pilote':
        requireRole(['recruteur']);
        (new MesCandidaturesController($pdo, $twig))->listePilote();
        break;

    // SFx21/22 — Détail d'une candidature (étudiant proprio, pilote ou admin)
    case 'candidature-detail':
        requireRole(['etudiant', 'recruteur', 'admin']);
        $id = (int) ($_GET['id'] ?? 0);
        (new CandidatureController($pdo, $twig))->detail($id);
        break;

    // SFx11 — statistiques (tous, y compris anonyme)
    case 'statistiques':
        (new StatistiquesController($pdo, $twig))->index();
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