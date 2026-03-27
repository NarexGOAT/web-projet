<?php
// 1. On importe le moule
require_once 'Utilisateur.php';

// 2. On vérifie si on a bien reçu des données du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. On récupère les infos du colis $_POST
    $email_du_formulaire = $_POST['email'];
    $mdp_du_formulaire = $_POST['mot_de_passe'];

    // 4. On crée notre objet Utilisateur (Instance)
    $monUtilisateur = new Utilisateur($email_du_formulaire, $mdp_du_formulaire);

    // 5. On vérifie si tout est bon
    if ($monUtilisateur->estValide()) {
        echo "Bravo ! L'objet Utilisateur est prêt pour : " . $monUtilisateur->getEmail();
        // C'est ici qu'on ajoutera plus tard l'envoi vers la Base de Données
    } else {
        echo "Erreur : Veuillez remplir tous les champs.";
    }
}
?>