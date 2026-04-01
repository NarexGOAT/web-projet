<?php

class PostulerController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function index()
    {
        session_start();

        //  Vérifier connexion
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        //  récupérer id offre
        if (!isset($_GET['id'])) {
            die("Offre introuvable");
        }

        $id_offre = $_GET['id'];
        $id_user = $_SESSION['user']['id'];

        // =========================
        //  TRAITEMENT FORMULAIRE
        // =========================
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // =====================
            //  éviter double candidature
            // =====================
            $check = $this->pdo->prepare("
                SELECT * FROM candidature 
                WHERE id_user = ? AND id_offre = ?
            ");
            $check->execute([$id_user, $id_offre]);

            if ($check->fetch()) {
                die("Tu as déjà postulé à cette offre");
            }

            // =====================
            //  UPLOAD CV
            // =====================
            $cv_name = null;

            if (!empty($_FILES['cv']['name'])) {

                $file = $_FILES['cv'];

                if ($file['size'] > 2 * 1024 * 1024) {
                    die("CV trop lourd (max 2Mo)");
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if ($ext !== 'pdf') {
                    die("Le CV doit être un PDF");
                }

                $cv_name = uniqid() . ".pdf";

                move_uploaded_file(
                    $file['tmp_name'],
                    "uploads/cv/" . $cv_name
                );
            }

            // =====================
            //  UPLOAD LETTRE 
            // =====================
            $lm_name = null;

            if (!empty($_FILES['lettre']['name'])) {

                $file = $_FILES['lettre'];

                if ($file['size'] > 2 * 1024 * 1024) {
                    die("Lettre trop lourde");
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if ($ext !== 'pdf') {
                    die("La lettre doit être un PDF");
                }

                $lm_name = uniqid() . ".pdf";

                move_uploaded_file(
                    $file['tmp_name'],
                    "uploads/lettres/" . $lm_name
                );
            }

            // =====================
            //  INSERT BDD
            // =====================
            $stmt = $this->pdo->prepare("
                INSERT INTO candidature 
                (cv, lm, id_user, id_offre)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $cv_name,
                $lm_name,
                $id_user,
                $id_offre
            ]);

            // =====================
            //  REDIRECTION
            // =====================
            $_SESSION['success'] = "Candidature envoyée avec succès !";

            header("Location: index.php?page=offre&id=" . $id_offre);
            exit;
        }

        // =========================
        //  Récupérer l’offre
        // =========================
        $stmt = $this->pdo->prepare("
            SELECT * FROM offre WHERE id_offre = ?
        ");
        $stmt->execute([$id_offre]);
        $offre = $stmt->fetch();

        if (!$offre) {
            die("Offre introuvable");
        }

        // =========================
        //  AFFICHAGE TWIG
        // =========================
        require 'views/postuler.html.twig';
    }
}