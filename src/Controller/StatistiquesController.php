<?php

class StatistiquesController
{
    private $pdo;
    private $twig;

    public function __construct($pdo, $twig)
    {
        $this->pdo  = $pdo;
        $this->twig = $twig;
    }

    // SFx11 — Carrousel d'informations statistiques sur les offres
    public function index(): void
    {
        // ============ 1) Nombre total d'offres ============
        $totalOffres = (int) $this->pdo->query("SELECT COUNT(*) FROM offre")->fetchColumn();

        // ============ 2) Nombre moyen de candidatures par offre ============
        $moyenneCandidatures = (float) $this->pdo->query("
            SELECT COALESCE(AVG(nb), 0) FROM (
                SELECT COUNT(c.id_candidature) AS nb
                FROM offre o
                LEFT JOIN candidature c ON c.id_offre = o.id_offre
                GROUP BY o.id_offre
            ) sub
        ")->fetchColumn();

        // ============ 3) Répartition par durée (groupée par tranches) ============
        $rows = $this->pdo->query("
            SELECT duree_stage, COUNT(*) AS nb
            FROM offre
            WHERE duree_stage IS NOT NULL
            GROUP BY duree_stage
        ")->fetchAll(PDO::FETCH_ASSOC);

        // tranches lisibles : 1-4, 5-8, 9-12, 13-16, 17-20, 21+
        $tranches = [
            '1-4 sem.'  => 0,
            '5-8 sem.'  => 0,
            '9-12 sem.' => 0,
            '13-16 sem.'=> 0,
            '17-20 sem.'=> 0,
            '21+ sem.'  => 0,
        ];
        foreach ($rows as $r) {
            $d = (int) $r['duree_stage'];
            $n = (int) $r['nb'];
            if      ($d <= 4)  $tranches['1-4 sem.']   += $n;
            elseif  ($d <= 8)  $tranches['5-8 sem.']   += $n;
            elseif  ($d <= 12) $tranches['9-12 sem.']  += $n;
            elseif  ($d <= 16) $tranches['13-16 sem.'] += $n;
            elseif  ($d <= 20) $tranches['17-20 sem.'] += $n;
            else               $tranches['21+ sem.']   += $n;
        }
        $repartitionDuree = [];
        foreach ($tranches as $label => $nb) {
            $repartitionDuree[] = ['label' => $label, 'nb' => $nb];
        }
        $maxDuree = max(array_column($repartitionDuree, 'nb')) ?: 1;

        // ============ 4) Top wishlist ============
        $topWishlist = $this->pdo->query("
            SELECT o.titre, e.nom_entreprise, COUNT(w.id_wishlist) AS nb
            FROM offre o
            LEFT JOIN entreprise e ON e.id_entreprise = o.id_entreprise
            LEFT JOIN wishlist  w ON w.id_offre = o.id_offre
            GROUP BY o.id_offre
            HAVING nb > 0
            ORDER BY nb DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo $this->twig->render('statistiques.html.twig', [
            'total_offres'         => $totalOffres,
            'moyenne_candidatures' => round($moyenneCandidatures, 2),
            'repartition_duree'    => $repartitionDuree,
            'max_duree'            => $maxDuree,
            'top_wishlist'         => $topWishlist,
        ]);
    }
}
