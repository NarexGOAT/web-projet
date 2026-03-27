<?php

// 1. On importe le module
require_once 'Utilisateur.php';

// 2. Vérifie si le formulaire est envoyé
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Récupération des données
    $email_du_formulaire = $_POST['email'] ?? '';
    $mdp_du_formulaire = $_POST['mot_de_passe'] ?? '';
    $conditions = $_POST['conditions'] ?? null;

    // 4. Création de l'objet
    $monUtilisateur = new Utilisateur($email_du_formulaire, $mdp_du_formulaire);

    // 5. Vérification
    if ($monUtilisateur->estValide() && $conditions) {

        echo "Bravo ! L'utilisateur est prêt.";

    

    } else {
        echo "Erreur : veuillez remplir tous les champs et accepter les conditions.";
    }
}
?>