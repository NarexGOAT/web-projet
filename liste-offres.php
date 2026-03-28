<?php
// On inclut notre moteur de pagination
require_once 'Pagination.php';

// On simule une fausse base de données avec un tableau d'offres
$toutesLesOffres = [
    ['titre' => 'Développeur Web Front-End', 'entreprise' => 'TechSolutions SAS', 'lieu' => 'Paris', 'duree' => '6 mois', 'domaine' => 'Bac+3/5', 'badge' => 'Nouveau', 'desc' => 'Rejoignez notre équipe technique pour développer nos nouvelles interfaces web en React...'],
    ['titre' => 'Assistant Chef de Projet', 'entreprise' => 'InnovCorp', 'lieu' => 'Lyon', 'duree' => '4 à 6 mois', 'domaine' => 'Marketing', 'badge' => '', 'desc' => 'Accompagnez notre équipe sur le lancement de la nouvelle gamme de produits...'],
    ['titre' => 'Ingénieur Cybersécurité', 'entreprise' => 'CyberDefense Pro', 'lieu' => 'Télétravail', 'duree' => '6 mois', 'domaine' => 'Sécurité', 'badge' => 'Urgent', 'desc' => 'Stage de fin d\'études : participez à l\'audit et à la sécurisation des infrastructures cloud...'],
    ['titre' => 'Data Analyst Junior', 'entreprise' => 'DataViz FR', 'lieu' => 'Bordeaux', 'duree' => '6 mois', 'domaine' => 'Data', 'badge' => '', 'desc' => 'Aidez-nous à transformer les données brutes de nos clients en tableaux de bord...'],
    ['titre' => 'Community Manager', 'entreprise' => 'GreenEco', 'lieu' => 'Strasbourg', 'duree' => '2 mois', 'domaine' => 'Communication', 'badge' => '', 'desc' => 'Gérez nos réseaux sociaux (LinkedIn, Instagram) et participez à la création de contenu...'],
    ['titre' => 'Assistant(e) Ressources Humaines', 'entreprise' => 'TechSolutions SAS', 'lieu' => 'Paris', 'duree' => '4 mois', 'domaine' => 'RH', 'badge' => 'Nouveau', 'desc' => 'Participez au processus de recrutement de nos futurs talents et aidez à l\'organisation...']
];

// On instancie la classe avec 3 offres par page
$pagination = new Pagination($toutesLesOffres, 3);

// On récupère uniquement les offres de la page actuelle
$offresDeLaPage = $pagination->itemsPage();
$pageActuelle = $pagination->getCurrentPage();
$totalDesPages = $pagination->getTotalPages();
?>

<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche d'offres de stage - AlloStage</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="liste-offres.css">
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
                <li><a href="liste-offres.php" class="actif">Offres de stages</a></li>
                <li><a href="entreprise.html">Entreprises</a></li>
            </ul>
        </nav>
        <div class="actions-entete">
            <a href="inscription.html" class="btn-secondaire">Inscription</a>
            <a href="connexion.html" class="btn-principal">Connexion</a>
        </div>
    </header>

    <main class="contenu fond-gris">
        
        <section class="en-tete-recherche">
            <h1>Trouvez le stage de vos rêves</h1>
            <p>Plus de 450 offres disponibles partout en France.</p>
            
            <form class="barre-recherche-avancee">
                <input type="text" placeholder="Quel métier recherchez-vous ?" class="input-metier">
                <input type="text" placeholder="📍 Ville ou code postal" class="input-lieu">
                <select class="select-duree">
                    <option value="">Toutes les durées</option>
                    <option value="1-2">1 à 2 mois</option>
                    <option value="3-5">3 à 5 mois</option>
                    <option value="6">6 mois et plus</option>
                </select>
                <button type="button" class="btn-principal">Rechercher</button>
            </form>
            
            <div class="resultats-tri">
                <!-- Idéalement, ce chiffre (452) sera aussi généré par PHP plus tard -->
                <span><strong>452</strong> offres correspondent à vos critères</span>
                <div class="tri">
                    <label for="tri-select">Trier par :</label>
                    <select id="tri-select">
                        <option value="recent">Les plus récentes</option>
                        <option value="pertinence">Pertinence</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="conteneur-liste">
            <div class="grille-offres">
                
                <!-- LA BOUCLE PHP QUI GÉNÈRE LES CARTES AUTOMATIQUEMENT -->
                <?php foreach ($offresDeLaPage as $offre): ?>
                    <article class="carte-offre">
                        <div class="en-tete-carte">
                            <?php if ($offre['badge'] !== ''): ?>
                                <?php if ($offre['badge'] === 'Urgent'): ?>
                                    <span class="badge-urgent"><?= htmlspecialchars($offre['badge']) ?></span>
                                <?php else: ?>
                                    <span class="badge-nouveau"><?= htmlspecialchars($offre['badge']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge-vide"></span>
                            <?php endif; ?>
                            <button class="btn-favori">🤍</button>
                        </div>
                        <h3 class="titre-offre"><?= htmlspecialchars($offre['titre']) ?></h3>
                        <p class="entreprise-offre"><strong><?= htmlspecialchars($offre['entreprise']) ?></strong> • <?= htmlspecialchars($offre['lieu']) ?></p>
                        <div class="tags-offre">
                            <span class="tag">📅 <?= htmlspecialchars($offre['duree']) ?></span>
                            <span class="tag">🎓 <?= htmlspecialchars($offre['domaine']) ?></span>
                        </div>
                        <p class="description"><?= htmlspecialchars($offre['desc']) ?></p>
                        <div class="actions-carte">
                            <a href="offre.html" class="btn-secondaire">Détails</a>
                            <a href="#" class="btn-postuler-lien">Postuler</a>
                        </div>
                    </article>
                <?php endforeach; ?>
                
            </div>

            <!-- LA PAGINATION DYNAMIQUE -->
            <nav class="pagination" aria-label="Pagination des offres">
                
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