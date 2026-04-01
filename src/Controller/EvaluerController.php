<?php

class EvaluerController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function index()
    {
        session_start();

        //  sécurité connexion
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        //  rôle autorisé
        if (
            $_SESSION['user']['role'] !== 'admin' &&
            $_SESSION['user']['role'] !== 'pilote'
        ) {
            die("Accès refusé");
        }

        $id_user = $_SESSION['user']['id'];
        $id_entreprise = $_GET['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $note = $_POST['note'];
            $commentaire = $_POST['commentaire'];

            //  éviter double évaluation
            $check = $this->pdo->prepare("
                SELECT * FROM evaluation 
                WHERE id_user = ? AND id_entreprise = ?
            ");
            $check->execute([$id_user, $id_entreprise]);

            if ($check->fetch()) {
                die("Tu as déjà évalué cette entreprise");
            }

            //  insert
            $stmt = $this->pdo->prepare("
                INSERT INTO evaluation (id_user, id_entreprise, note, commentaire)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $id_user,
                $id_entreprise,
                $note,
                $commentaire
            ]);

            // 🔁 retour
            header("Location: index.php?page=entreprise&id=" . $id_entreprise);
            exit;
        }
    }
}