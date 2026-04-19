-- ============================================
-- Smart Trash - Création d'un utilisateur dédié
-- Remplace l'utilisation de root depuis le site web
-- L'utilisateur smart_user a accès UNIQUEMENT
-- à la base smart_trash depuis le réseau Docker
-- ============================================

-- Créer l'utilisateur dédié
-- '172.%' = uniquement depuis le réseau Docker interne
-- (au lieu de '%' qui autorise tout le monde)
CREATE USER IF NOT EXISTS 'smart_user'@'172.%' IDENTIFIED BY 'poubelle2026';

-- Donner les droits uniquement sur la base smart_trash
GRANT SELECT, INSERT, UPDATE, DELETE ON smart_trash.* TO 'smart_user'@'172.%';

-- Appliquer les changements
FLUSH PRIVILEGES;