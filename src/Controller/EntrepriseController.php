<?php

class EntrepriseController
{
    private \PDO $pdo;
    private \Twig\Environment $twig;

    public function __construct(\PDO $pdo, \Twig\Environment $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    public function liste(): void
    {
        $sqlDomaines = "
            SELECT DISTINCT domaine
            FROM entreprise
            WHERE domaine IS NOT NULL AND domaine <> ''
            ORDER BY domaine
        ";
        $domaines = $this->pdo->query($sqlDomaines)->fetchAll(\PDO::FETCH_COLUMN);

        $sqlVilles = "
            SELECT DISTINCT ville
            FROM entreprise
            WHERE ville IS NOT NULL AND ville <> ''
            ORDER BY ville
        ";
        $villes = $this->pdo->query($sqlVilles)->fetchAll(\PDO::FETCH_COLUMN);

        $sql = "
            SELECT 
                e.*,
                COUNT(o.id_offre) AS nb_offres
            FROM entreprise e
            LEFT JOIN offre o ON o.id_entreprise = e.id_entreprise
        ";

        $conditions = [];
        $params = [];

        if (!empty($_GET['nom'])) {
            $conditions[] = 'e.nom_entreprise LIKE :nom';
            $params['nom'] = '%' . $_GET['nom'] . '%';
        }

        if (!empty($_GET['domaine']) && $_GET['domaine'] !== 'Tous les domaines') {
            $conditions[] = 'e.domaine = :domaine';
            $params['domaine'] = $_GET['domaine'];
        }

        if (!empty($_GET['ville']) && $_GET['ville'] !== 'Toutes les villes') {
            $conditions[] = 'e.ville = :ville';
            $params['ville'] = $_GET['ville'];
        }

        if (!empty($_GET['taille']) && $_GET['taille'] !== 'Toutes les tailles') {
            if ($_GET['taille'] === 'Petite entreprise') {
                $conditions[] = 'e.taille < 50';
            } elseif ($_GET['taille'] === 'PME') {
                $conditions[] = 'e.taille >= 50 AND e.taille < 250';
            } elseif ($_GET['taille'] === 'Grande entreprise') {
                $conditions[] = 'e.taille >= 250';
            }
        }

        if (!empty($_GET['offres']) && $_GET['offres'] !== 'Peu importe') {
            if ($_GET['offres'] === '0 offre') {
                $conditions[] = 'COUNT(o.id_offre) = 0';
            } elseif ($_GET['offres'] === '1 à 5 offres') {
                $conditions[] = 'COUNT(o.id_offre) BETWEEN 1 AND 5';
            } elseif ($_GET['offres'] === '6 à 10 offres') {
                $conditions[] = 'COUNT(o.id_offre) BETWEEN 6 AND 10';
            } elseif ($_GET['offres'] === 'Plus de 10 offres') {
                $conditions[] = 'COUNT(o.id_offre) > 10';
            }
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY e.id_entreprise ORDER BY e.nom_entreprise';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $entreprises = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('liste-entreprises.html.twig', [
            'entreprises'        => $entreprises,
            'nombre_entreprises' => count($entreprises),
            'filtres'            => $_GET,
            'domaines'           => $domaines,
            'villes'             => $villes,
        ]);
    }

    public function detail(int $id): void
    {
        $sqlEnt = "
            SELECT *
            FROM entreprise
            WHERE id_entreprise = :id
        ";
        $stmt = $this->pdo->prepare($sqlEnt);
        $stmt->execute(['id' => $id]);
        $entreprise = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$entreprise) {
            http_response_code(404);
            echo 'Entreprise introuvable';
            return;
        }

        $sqlNote = "
            SELECT AVG(note) AS moyenne
            FROM evaluation
            WHERE id_entreprise = :id";
        $stmtNote = $this->pdo->prepare($sqlNote);
        $stmtNote->execute(['id' => $id]);
        $moyenne_note = $stmtNote->fetchColumn();

        $sqlOffres = "
            SELECT *
            FROM offre
            WHERE id_entreprise = :id
            ORDER BY date_publication DESC
        ";
        $stmtOffres = $this->pdo->prepare($sqlOffres);
        $stmtOffres->execute(['id' => $id]);
        $offres = $stmtOffres->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('entreprise.html.twig', [
            'entreprise'   => $entreprise,
            'moyenne_note' => $moyenne_note,
            'offres'       => $offres,
            'offre_count'  => count($offres),
        ]);
    }
}