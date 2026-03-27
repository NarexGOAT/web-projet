<?php

class Utilisateur {
    // Les propriétés (les caractéristiques de l'utilisateur)
    private $email;
    private $mot_de_passe;

    // Le "Constructor" : c'est ce qui s'exécute quand on crée un utilisateur
    public function __construct($mail, $mdp) {
        $this->email = $mail;
        $this->mot_de_passe = $mdp;
    }

    // Une méthode (une action) pour vérifier si les champs ne sont pas vides
    public function estValide() {
        if (!empty($this->email) && !empty($this->mot_de_passe)) {
            return true;
        }
        return false;
    }

    // Un "Getter" pour récupérer l'email proprement
    public function getEmail() {
        return $this->email;
    }
}

?>