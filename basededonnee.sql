CREATE TABLE utilisateur (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    mot_de_passe VARCHAR(255),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_role,
    FOREIGN KEY (id_role) REFERENCES role(id_role)
);

CREATE TABLE role (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50)
);

CREATE TABLE offre (
    id_offre INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150),
    description TEXT,
    remuneration DECIMAL(10,2),
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    duree_stage VARCHAR(50),
    id_entreprise INT,
    competence VARCHAR(100),
    FOREIGN KEY (id_entreprise) REFERENCES entreprise(id_entreprise)
);

CREATE TABLE candidature (
    id_candidature INT AUTO_INCREMENT PRIMARY KEY,
    cv TEXT,
    lm TEXT,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_user INT,
    id_offre INT,

    FOREIGN KEY (id_user) REFERENCES utilisateur(id_user),
    FOREIGN KEY (id_offre) REFERENCES offre(id_offre)
);

CREATE TABLE wishlist (
    id_wishlist INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    id_offre INT,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_user) REFERENCES utilisateur(id_user),
    FOREIGN KEY (id_offre) REFERENCES offre(id_offre)
);

CREATE TABLE evaluation (
    id_eval INT AUTO_INCREMENT PRIMARY KEY,
    note INT,
    commentaire TEXT,
    id_user INT,
    id_entreprise INT,

    FOREIGN KEY (id_user) REFERENCES utilisateur(id_user),
    FOREIGN KEY (id_entreprise) REFERENCES entreprise(id_entreprise)
);

CREATE TABLE entreprise (
    id_entreprise INT AUTO_INCREMENT PRIMARY KEY,
    nom_entreprise VARCHAR(100),
    description TEXT,
    email VARCHAR(50),
    telephone INT(20)
);