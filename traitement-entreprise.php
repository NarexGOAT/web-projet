<?php
// On vérifie que le formulaire a bien été envoyé via la méthode POST[cite: 5]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Récupération des données du formulaire (depuis la superglobale $_POST)
    // On utilise l'opérateur ?? (null coalescing) pour éviter les erreurs si un champ est vide
    $nom = $_POST['nom_entreprise'] ?? '';
    $domaine = $_POST['domaine'] ?? '';
    $taille = $_POST['taille'] ?? '';
    $ville = $_POST['ville'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $email = $_POST['email'] ?? '';
    $description = $_POST['description'] ?? '';

    $host = '127.0.0.1';
    $dbname = 'stage_db'; 
    $username = 'root';
    $password = '';

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        
        // Configuration pour que PDO lève des exceptions en cas d'erreur (très pratique pour déboguer)[cite: 5]
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());[cite: 5]
    }

    // 3. La requête préparée (Sécurité maximale contre les injections SQL)[cite: 5]
    // On met des "marqueurs" (les mots avec les deux points :nom) à la place des variables
    $sql = "INSERT INTO entreprise (nom_entreprise, description, domaine, ville, taille, email, telephone) 
            VALUES (:nom_entreprise, :description, :domaine, :ville, :taille, :email, :telephone)";

    // Étape 1 : Préparation du modèle de requête par le serveur[cite: 5]
    $stmt = $pdo->prepare($sql);[cite: 5]

    // Étape 2 : Exécution de la requête en liant les marqueurs à nos variables[cite: 5]
    try {
        $stmt->execute([
            'nom_entreprise' => $nom,
            'description'  => $description,
            'domaine'      => $domaine,
            'ville'        => $ville,
            'taille'       => $taille,
            'email'        => $email,
            'telephone'    => $telephone
        ]);[cite: 5]

        // Si on arrive ici, c'est que l'insertion a réussi !
        // echo "Nouvelle entreprise créée avec l'ID : " . $pdo->lastInsertId();[cite: 5]
        
        // La bonne pratique est de rediriger l'utilisateur vers la liste des entreprises après une création
        header('Location: liste-entreprises.php');
        exit;

    } catch (PDOException $e) {
        // En cas d'erreur lors de l'insertion (