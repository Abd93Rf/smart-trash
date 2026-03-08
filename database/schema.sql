-- ============================================
-- Smart Trash - Base de données
-- Projet BTS CIEL
-- ============================================

-- Création de la base de données
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
    niveau DECIMAL(5, 2) NOT NULL COMMENT 'Pourcentage de remplissage',
    poids DECIMAL(6, 2) NOT NULL COMMENT 'Poids en kg',
    temperature DECIMAL(5, 2) NOT NULL COMMENT 'Température en °C',
    date_mesure DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
);

-- ============================================
-- Table des alertes
-- ============================================
CREATE TABLE IF NOT EXISTS alertes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_poubelle INT NOT NULL,
    type_alerte ENUM('pleine', 'temperature', 'maintenance') DEFAULT 'pleine',
    message VARCHAR(255) DEFAULT NULL,
    statut ENUM('active', 'resolue') DEFAULT 'active',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_resolution DATETIME DEFAULT NULL,
    FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
);

-- ============================================
-- Données de test
-- ============================================

-- Utilisateur admin (mot de passe : admin123)
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
('Admin', 'admin@smarttrash.fr', '$2y$12$H5NbkStYOUJhODIrvKGTCexGLTAhNdeh7H0wu2OqNIlG5m5dQCoMi', 'admin');

-- Poubelles de test (coordonnées à Tunis)
INSERT INTO poubelles (nom, adresse, latitude, longitude, statut) VALUES
                                                                      ('Poubelle A', 'Rue de la République', 48.9362, 2.3574, 'actif'),
                                                                      ('Poubelle B', 'Place du Caquet', 48.9345, 2.3580, 'actif'),
                                                                      ('Poubelle C', 'Avenue du Président Wilson', 48.9310, 2.3530, 'actif'),
                                                                      ('Poubelle D', 'Rue Gabriel Péri', 48.9380, 2.3550, 'actif'),
                                                                      ('Poubelle E', 'Boulevard Marcel Sembat', 48.9290, 2.3610, 'maintenance');

-- Mesures simulées pour les dernières 48h
INSERT INTO mesures (id_poubelle, niveau, poids, temperature, date_mesure) VALUES
-- Poubelle A
(1, 25.00, 3.20, 22.50, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(1, 40.00, 5.10, 23.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(1, 55.00, 7.80, 24.50, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(1, 72.00, 10.20, 25.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 85.00, 12.40, 26.00, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- Poubelle B
(2, 10.00, 1.50, 21.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(2, 30.00, 4.00, 22.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(2, 45.00, 6.30, 22.50, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(2, 60.00, 8.50, 23.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(2, 68.00, 9.80, 23.50, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- Poubelle C
(3, 50.00, 7.00, 20.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(3, 65.00, 9.20, 21.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(3, 78.00, 11.00, 22.00, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(3, 88.00, 13.50, 23.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(3, 95.00, 15.00, 24.00, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- Poubelle D
(4, 5.00, 0.80, 19.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(4, 15.00, 2.10, 20.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(4, 25.00, 3.50, 20.50, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(4, 35.00, 5.00, 21.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(4, 42.00, 6.00, 21.50, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- Poubelle E
(5, 30.00, 4.20, 25.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(5, 55.00, 7.50, 26.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(5, 71.00, 10.00, 27.00, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(5, 80.00, 11.80, 28.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(5, 90.00, 14.00, 29.50, DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- Alertes de test
INSERT INTO alertes (id_poubelle, type_alerte, message, statut) VALUES
(1, 'pleine', 'Niveau de remplissage à 85%', 'active'),
(3, 'pleine', 'Niveau de remplissage à 95%', 'active'),
(5, 'pleine', 'Niveau de remplissage à 90%', 'active'),
(5, 'temperature', 'Température élevée : 29.5°C', 'active');
