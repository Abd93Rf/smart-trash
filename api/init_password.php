<?php
// ============================================
// Smart Trash - Initialisation du mot de passe
// Ce script est exécuté automatiquement au
// démarrage du conteneur web.
//
// Le mot de passe est lu depuis la variable
// d'environnement ADMIN_PASSWORD définie dans
// le docker-compose.yml (pas en dur dans le code)
// ============================================

// Récupérer le mot de passe depuis la variable d'environnement
$adminPassword = getenv('ADMIN_PASSWORD');

// Si la variable n'est pas définie, utiliser une valeur par défaut
if (!$adminPassword) {
    echo "ATTENTION : Variable ADMIN_PASSWORD non définie. Utilisation du mot de passe par défaut.\n";
    $adminPassword = 'admin123';
}

// Attendre que MariaDB soit prêt
$maxTentatives = 30;
$pdo = null;

for ($i = 0; $i < $maxTentatives; $i++) {
    try {
        $pdo = new PDO(
            "mysql:host=db;dbname=smart_trash;charset=utf8",
            "smart_user",
            "poubelle2026",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        break;
    } catch (PDOException $e) {
        echo "En attente de MariaDB... ($i/$maxTentatives)\n";
        sleep(2);
    }
}

if (!$pdo) {
    echo "ERREUR : Impossible de se connecter à MariaDB.\n";
    exit(1);
}

// Générer le hash bcrypt et mettre à jour le mot de passe admin
$hash = password_hash($adminPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = :hash WHERE email = 'admin@smarttrash.fr'");
$stmt->execute(['hash' => $hash]);

echo "Mot de passe admin mis à jour avec succès.\n";