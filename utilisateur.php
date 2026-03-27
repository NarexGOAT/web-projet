<?php
class Utilisateur {
    // Propriétés privées (Encapsulation : sécurité !)
    private $email;
    private $mot_de_passe;

    // Le Constructeur : il crée l'objet avec les infos reçues
    public function __construct($mail_recu, $mdp_recu) {
        $this->email = $mail_recu;
        $this->mot_de_passe = $mdp_recu;
    }

    // Une méthode pour vérifier si les champs sont remplis
    public function estValide() {
        return !empty($this->email) && !empty($this->mot_de_passe);
    }

    // Un "Getter" pour récupérer l'email sans toucher à la variable privée
    public function getEmail() {
        return $this->email;
    }
}
?>