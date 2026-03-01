CREATE DATABASE smart_trash CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE smart_trash;

-- Table poubelles
CREATE TABLE poubelles (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           nom VARCHAR(100) NOT NULL,
                           latitude DOUBLE NOT NULL,
                           longitude DOUBLE NOT NULL,
                           statut ENUM('actif', 'maintenance') DEFAULT 'actif',
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table mesures (historique complet)
CREATE TABLE mesures (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         id_poubelle INT NOT NULL,
                         niveau INT NOT NULL,
                         poids DECIMAL(6,2) NOT NULL,
                         temperature DECIMAL(5,2) NOT NULL,
                         date_mesure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
);

-- Table alertes
CREATE TABLE alertes (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         id_poubelle INT NOT NULL,
                         type_alerte VARCHAR(50) NOT NULL,
                         statut ENUM('active', 'resolue') DEFAULT 'active',
                         date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         FOREIGN KEY (id_poubelle) REFERENCES poubelles(id)
);

-- Table utilisateurs
CREATE TABLE utilisateurs (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              username VARCHAR(50) UNIQUE,
                              password VARCHAR(255),
                              role ENUM('admin','user') DEFAULT 'user'
);