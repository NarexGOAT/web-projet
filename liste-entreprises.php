<?php
// On inclut notre moteur de pagination (le même fichier que pour les offres !)
require_once 'Pagination.php';

// On simule notre base de données d'entreprises
$toutesLesEntreprises = [
    ['nom' => 'TechSolutions SAS', 'secteur' => 'Informatique / Éditeur logiciel', 'lieu' => 'Paris', 'taille' => '10-50', 'note' => '★★★★☆', 'avis' => 42, 'offres' => 3, 'desc' => 'Spécialiste de la création de logiciels SaaS pour les professionnels de la santé. Une équipe dynamique en plein cœur de Paris.', 'logo_txt' => 'TS', 'logo_css' => 'logo-ts'],
    ['nom' => 'InnovCorp', 'secteur' => 'Marketing & Stratégie', 'lieu' => 'Lyon', 'taille' => '50-200', 'note' => '★★★★★', 'avis' => 18, 'offres' => 1, 'desc' => 'Agence de communication à 360°. Nous accompagnons les grandes marques dans leur transition numérique et publicitaire.', 'logo_txt' => 'IC', 'logo_css' => 'logo-ic'],
    ['nom' => 'CyberDefense Pro', 'secteur' => 'Cybersécurité', 'lieu' => 'Rennes', 'taille' => '+200', 'note' => '★★★☆☆', 'avis' => 5, 'offres' => 5, 'desc' => 'Experts en audit de sécurité, tests d\'intrusion et sécurisation d\'infrastructures cloud pour les grands comptes.', 'logo_txt' => 'CD', 'logo_css' => 'logo-cd'],
    ['nom' => 'GreenEco', 'secteur' => 'Environnement & Énergie', 'lieu' => 'Strasbourg', 'taille' => '10-50', 'note' => '★★★★☆', 'avis' => 12, 'offres' => 0, 'desc' => 'Pionniers dans le développement de solutions logicielles pour l\'optimisation énergétique des bâtiments industriels.', 'logo_txt' => 'GE', 'logo_css' => 'logo-ge'],
    ['nom' => 'DataViz FR', 'secteur' => 'Big Data & Analytics', 'lieu' => 'Bordeaux', 'taille' => '1-10', 'note' => '★★★★★', 'avis' => 3, 'offres' => 2, 'desc' => 'Jeune startup spécialisée dans la transformation de données complexes en tableaux de bord interactifs.', 'logo_txt' => 'DV', 'logo_css' => 'logo-dv'],
    ['nom' => 'BuildGreen', 'secteur' => 'BTP & Éco-conception', 'lieu' => 'Nantes', 'taille' => '50-200', 'note' => '★★★★☆', 'avis' => 24, 'offres' => 0, 'desc' => 'Leader de la construction de bâtiments à énergie positive et de la rénovation thermique.', 'logo_txt' => 'BG', 'logo_css' => 'logo-bg']
];

// On instancie la classe : cette fois on affiche 4 entreprises par page (car les cartes sont plus larges)
$pagination = new Pagination($toutesLesEntreprises, 4);

// On récupère les infos pour la page actuelle
$entreprisesDeLaPage = $pagination->itemsPage();
$pageActuelle = $pagination->getCurrentPage();
$totalDesPages = $pagination->getTotalPages();
?>

<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entreprises Partenaires - AlloStage</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="liste-entreprises.css">
</head>
<body>
    <header class="entete">
        <div class="logo">
            <a href="index.html">
                <img src="logo.png" alt="Accueil AlloStage" class="logo-img" style="height: 70px; object-fit: contain;">
            </a>
        </div>
        <nav class="navigation">
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="liste-offres.php">Offres de stages</a></li>
                <!-- On met "Entreprises" en actif -->
                <li><a href="liste-entreprises.php" class="actif">Entreprises</a></li>
            </ul>
        </nav>
        <div class="actions-entete">
            <a href="inscription.html" class="btn-secondaire">Inscription</a>
            <a href="connexion.html" class="btn-principal">Connexion</a>
        </div>
    </header>

    <main class="contenu fond-gris">
        
        <section class="en-tete-annuaire">
            <h1>Découvrez nos entreprises partenaires</h1>
            <p>Trouvez l'environnement de travail qui vous correspond parmi plus de 150 entreprises.</p>
            
            <form class="barre-recherche-large">
                <input type="text" placeholder="Nom de l'entreprise, secteur, mot-clé...">
                <select class="filtre-select">
                    <option value="">Tous les secteurs</option>
                    <option value="it">Informatique & Web</option>
                    <option value="marketing">Marketing & Com</option>
                    <option value="cyber">Cybersécurité</option>
                </select>
                <button type="button" class="btn-principal">Rechercher</button>
            </form>
        </section>

        <section class="conteneur-entreprises">
            <div class="grille-entreprises">
                
                <!-- LA BOUCLE PHP QUI GÉNÈRE LES CARTES ENTREPRISES -->
                <?php foreach ($entreprisesDeLaPage as $entreprise): ?>
                    <article class="carte-annuaire boite-blanche">
                        <div class="haut-carte">
                            <!-- Le PHP génère la bonne couleur (logo_css) et les bonnes initiales (logo_txt) -->
                            <div class="logo-entreprise-carre <?= htmlspecialchars($entreprise['logo_css']) ?>">
                                <?= htmlspecialchars($entreprise['logo_txt']) ?>
                            </div>
                            <div class="infos-titre">
                                <h2><?= htmlspecialchars($entreprise['nom']) ?></h2>
                                <span class="secteur"><?= htmlspecialchars($entreprise['secteur']) ?></span>
                                <div class="note">
                                    <span class="etoiles"><?= $entreprise['note'] ?></span>
                                    <span class="avis-count">(<?= $entreprise['avis'] ?> avis)</span>
                                </div>
                            </div>
                        </div>
                        <p class="description-courte"><?= htmlspecialchars($entreprise['desc']) ?></p>
                        <div class="tags-entreprise">
                            <span class="tag">📍 <?= htmlspecialchars($entreprise['lieu']) ?></span>
                            <span class="tag">👥 <?= htmlspecialchars($entreprise['taille']) ?> salariés</span>
                        </div>
                        <div class="actions-annuaire">
                            
                            <!-- Logique d'affichage dynamique pour le texte des offres -->
                            <?php if ($entreprise['offres'] == 0): ?>
                                <span class="compteur-offres text-vide">Aucune offre</span>
                            <?php elseif ($entreprise['offres'] >= 3): ?>
                                <span class="compteur-offres text-urgent"><strong><?= $entreprise['offres'] ?></strong> offres actives</span>
                            <?php else: ?>
                                <span class="compteur-offres"><strong><?= $entreprise['offres'] ?></strong> offre(s) en cours</span>
                            <?php endif; ?>

                            <a href="entreprise.html" class="btn-secondaire btn-petit">Voir le profil</a>
                        </div>
                    </article>
                <?php endforeach; ?>
                
            </div>

            <!-- LA PAGINATION DYNAMIQUE (Identique aux offres) -->
            <nav class="pagination" aria-label="Pagination des entreprises">
                
                <?php if ($pageActuelle > 1): ?>
                    <a href="?page=<?= $pageActuelle - 1 ?>" class="page-lien precedent">← Précédent</a>
                <?php else: ?>
                    <span class="page-lien precedent desactive">← Précédent</span>
                <?php endif; ?>

                <span class="page-lien actif"><?= $pageActuelle ?> sur <?= $totalDesPages ?></span>

                <?php if ($pageActuelle < $totalDesPages): ?>
                    <a href="?page=<?= $pageActuelle + 1 ?>" class="page-lien suivant">Suivant →</a>
                <?php else: ?>
                    <span class="page-lien suivant desactive">Suivant →</span>
                <?php endif; ?>

            </nav>

        </section>
    </main>

    <footer class="pied-de-page">
        <p>&copy; 2026 AlloStage. Tous droits réservés.</p>
    </footer>
</body>
</html>