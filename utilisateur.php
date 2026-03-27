<?php

class Utilisateur {

    private $email;
    private $mot_de_passe;

    public function __construct($mail_recu, $mdp_recu) {

        $this->email = $mail_recu;

        // Vérifie longueur avant hash
        if (strlen($mdp_recu) >= 8) {
            $this->mot_de_passe = password_hash($mdp_recu, PASSWORD_DEFAULT);
        } else {
            $this->mot_de_passe = null;
        }
    }

    public function estValide() {
        return !empty($this->email)
            && filter_var($this->email, FILTER_VALIDATE_EMAIL)
            && !empty($this->mot_de_passe);
    }

    public function getEmail() {
        return $this->email;
    }

    public function getMotDePasse() {
        return $this->mot_de_passe;
    }
}
?>