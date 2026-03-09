<?php
// ============================================
// Smart Trash - Initialisation du mot de passe
// Ce script est exécuté automatiquement au
// démarrage du conteneur web.
// ============================================

// Attendre que MariaDB soit prêt
$maxTentatives = 30;
$pdo = null;

for ($i = 0; $i < $maxTentatives; $i++) {
    try {
        $pdo = new PDO(
            "mysql:host=db;dbname=smart_trash;charset=utf8",
            "root",
            "password",
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

// Générer le bon hash et mettre à jour le mot de passe admin
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = :hash WHERE email = 'admin@smarttrash.fr'");
$stmt->execute(['hash' => $hash]);

echo "Mot de passe admin mis à jour avec succès.\n";
