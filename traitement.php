<?php

require_once 'Utilisateur.php';
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Récupération des données
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirm_mot_de_passe'] ?? '';
    $conditions = $_POST['conditions'] ?? null;

    // Création de l'objet utilisateur
    $utilisateur = new Utilisateur($email, $mot_de_passe);

    // Vérifications
    if ($utilisateur->estValide() && $conditions) {

        if ($mot_de_passe === $confirmation) {

            echo "Bravo ! L'utilisateur est valide.";

            // 👉 Étape suivante : insertion en base

        } else {
            echo "Erreur : les mots de passe ne correspondent pas.";
        }

    } else {
        echo "Erreur : veuillez remplir correctement tous les champs et accepter les conditions.";
    }

} else {
    echo "Erreur : formulaire non envoyé.";
}
?>