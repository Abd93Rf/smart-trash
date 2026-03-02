<?php
/**
 * Configuration SQLite pour Smart Trash
 * Pas de serveur MySQL nécessaire !
 */

// Chemin vers la base de données SQLite
define('DB_PATH', __DIR__ . '/../smart_trash.db');

// Création de la connexion PDO SQLite
try {
    $pdo = new PDO(
        "sqlite:" . DB_PATH,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Activer les clés étrangères (comme dans votre projet C++)
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // Initialiser le schéma automatiquement
    initSchema($pdo);
    
} catch (PDOException $e) {
    die("Erreur SQLite : " . $e->getMessage());
}

/**
 * Initialisation automatique du schéma
 * Créé les tables et insère les données de test si la base est vide
 */
function initSchema($pdo) {
    // Créer les tables (IF NOT EXISTS = ne recrée pas si existe déjà)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS poubelles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            latitude REAL NOT NULL,
            longitude REAL NOT NULL,
            statut TEXT DEFAULT 'Actif' CHECK(statut IN ('Actif', 'Maintenance')),
            date_creation TEXT DEFAULT (datetime('now'))
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mesures (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_poubelle INTEGER NOT NULL,
            niveau INTEGER NOT NULL CHECK(niveau >= 0 AND niveau <= 100),
            poids REAL NOT NULL,
            temperature REAL NOT NULL,
            date_mesure TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alertes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_poubelle INTEGER NOT NULL,
            type_alerte TEXT DEFAULT 'Pleine',
            statut TEXT DEFAULT 'Active' CHECK(statut IN ('Active', 'Résolue')),
            date_creation TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
        )
    ");
    
    // Vérifier si la base est vide
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM poubelles");
    $count = $stmt->fetch()['count'];
    
    // Insérer les données de test si vide
    if ($count == 0) {
        // Données de test - 5 poubelles
        $pdo->exec("
            INSERT INTO poubelles (nom, latitude, longitude, statut) VALUES
            ('Poubelle Centre Ville #1', 36.8065, 10.1815, 'Actif'),
            ('Poubelle République', 36.8125, 10.1875, 'Actif'),
            ('Poubelle Marché', 36.8095, 10.1795, 'Actif'),
            ('Poubelle Gare', 36.8055, 10.1845, 'Actif'),
            ('Poubelle Parc', 36.8145, 10.1925, 'Actif')
        ");
        
        // Mesures de test
        $pdo->exec("
            INSERT INTO mesures (id_poubelle, niveau, poids, temperature) VALUES
            (1, 78, 15.4, 24.5),
            (2, 45, 9.2, 25.1),
            (3, 92, 18.7, 26.3),
            (4, 35, 7.8, 23.8),
            (5, 56, 11.3, 24.2)
        ");
        
        // Alertes de test
        $pdo->exec("
            INSERT INTO alertes (id_poubelle, type_alerte, statut) VALUES
            (1, 'Pleine', 'Active'),
            (3, 'Pleine', 'Active')
        ");
    }
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
