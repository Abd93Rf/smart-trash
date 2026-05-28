-- ============================================
-- Smart Trash - Base de données
-- Projet BTS CIEL
-- ============================================

CREATE DATABASE IF NOT EXISTS smart_trash CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE smart_trash;

-- ============================================
-- Table des utilisateurs
-- ============================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operateur') DEFAULT 'operateur',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    );

-- ============================================
-- Table des poubelles
-- ============================================
CREATE TABLE IF NOT EXISTS poubelles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    latitude DECIMAL(10, 6) NOT NULL,
    longitude DECIMAL(10, 6) NOT NULL,
    statut ENUM('actif', 'maintenance', 'inactif') DEFAULT 'actif',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    );

-- ============================================
-- Table des mesures (données capteurs)
-- ============================================
CREATE TABLE IF NOT EXISTS mesures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_poubelle INT NOT NULL,
    niveau DECIMAL(5, 2) NOT NULL COMMENT 'Pourcentage de remplissage (ultrason)',
    poids DECIMAL(6, 2) NOT NULL COMMENT 'Poids en kg',
    temperature DECIMAL(5, 2) NOT NULL COMMENT 'Température en °C',
    humidite DECIMAL(5, 2) DEFAULT NULL COMMENT 'Humidité en %',
    date_mesure DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
    );

-- ============================================
-- Table des alertes
-- ============================================
CREATE TABLE IF NOT EXISTS alertes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_poubelle INT NOT NULL,
    type_alerte ENUM('pleine', 'critique', 'surcharge', 'temperature', 'humidite', 'maintenance') DEFAULT 'pleine',
    message VARCHAR(255) DEFAULT NULL,
    statut ENUM('active', 'resolue') DEFAULT 'active',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_resolution DATETIME DEFAULT NULL,
    FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
    );

-- ============================================
-- Utilisateur admin
-- Le hash du mot de passe est généré au démarrage
-- par api/init_password.php via la variable
-- d'environnement ADMIN_PASSWORD du docker-compose
-- Utilisateur admin (le hash sera régénéré par init_password.php)
-- ===========================================
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
    ('Admin', 'admin@smarttrash.fr', '$2y$10$placeholder', 'admin');