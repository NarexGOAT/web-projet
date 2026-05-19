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

    // SFx21 — Liste des candidatures de l'étudiant connecté
    public function liste()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $id_user = (int) $_SESSION['user']['id'];

        $stmt = $this->pdo->prepare("
            SELECT c.*, o.titre, o.duree_stage, o.competence,
                   e.nom_entreprise, e.ville
            FROM candidature c
            JOIN offre o ON c.id_offre = o.id_offre
            LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            WHERE c.id_user = ?
            ORDER BY c.date_envoi DESC
        ");
        $stmt->execute([$id_user]);
        $candidatures = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // flash messages
        $flashOk  = $_SESSION['flash_ok']  ?? null;
        $flashErr = $_SESSION['flash_err'] ?? null;
        unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

        echo $this->twig->render('mes-candidatures.html.twig', [
            'candidatures' => $candidatures,
            'flash_ok'     => $flashOk,
            'flash_err'    => $flashErr,
        ]);
    }

    // SFx22 — Vue pilote : candidatures des élèves du pilote connecté
    public function listePilote()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=connexion');
            exit;
        }

        $id_pilote = (int) $_SESSION['user']['id'];

        $stmt = $this->pdo->prepare("
            SELECT c.*, o.titre, o.duree_stage, o.competence,
                   e.nom_entreprise, e.ville,
                   u.id_user AS etudiant_id,
                   u.nom AS etudiant_nom, u.prenom AS etudiant_prenom,
                   u.email AS etudiant_email
            FROM candidature c
            JOIN offre o ON c.id_offre = o.id_offre
            LEFT JOIN entreprise e ON o.id_entreprise = e.id_entreprise
            JOIN utilisateur u ON c.id_user = u.id_user
            WHERE u.id_pilote = ?
            ORDER BY c.date_envoi DESC
        ");
        $stmt->execute([$id_pilote]);
        $candidatures = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Liste des étudiants pour filtrage côté client
        $etudiants = [];
        foreach ($candidatures as $c) {
            $eid = $c['etudiant_id'];
            if (!isset($etudiants[$eid])) {
                $etudiants[$eid] = [
                    'id'     => $eid,
                    'nom'    => trim($c['etudiant_prenom'] . ' ' . $c['etudiant_nom']),
                ];
            }
        }

        echo $this->twig->render('candidatures-pilote.html.twig', [
            'candidatures' => $candidatures,
            'etudiants'    => array_values($etudiants),
            'total'        => count($candidatures),
        ]);
    }
}
