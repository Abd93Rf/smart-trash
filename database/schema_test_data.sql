-- ============================================
-- Smart Trash - Données de test (DEV UNIQUEMENT)
-- ⚠️  NE PAS charger ce fichier en production
--
-- Usage :
--   docker exec -i smart_trash_db mysql -uroot -ppassword smart_trash \
--     < database/schema_test_data.sql
-- ============================================

USE smart_trash;

-- Poubelles de test (Saint-Denis)
INSERT INTO poubelles (nom, adresse, latitude, longitude, statut) VALUES
                                                                      ('Poubelle A', 'Rue de la République',  48.9362, 2.3574, 'actif'),
                                                                      ('Poubelle B', 'Rue Gabriel Péri',       48.9355, 2.3555, 'actif'),
                                                                      ('Poubelle C', 'Place du Caquet',        48.9345, 2.3580, 'actif'),
                                                                      ('Poubelle D', 'Rue de Strasbourg',      48.9338, 2.3562, 'actif'),
                                                                      ('Poubelle E', 'Place du 8 Mai 1945',    48.9330, 2.3545, 'maintenance');

-- Mesures simulées avec humidité
INSERT INTO mesures (id_poubelle, niveau, poids, temperature, humidite, date_mesure) VALUES
-- Poubelle A (remplissage progressif)
(1, 25.00,  3.20, 22.50, 45.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(1, 40.00,  5.10, 23.00, 48.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(1, 55.00,  7.80, 24.50, 52.00, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(1, 72.00, 10.20, 25.00, 55.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 85.00, 12.40, 26.00, 58.00, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
-- Poubelle B (niveau moyen)
(2, 10.00,  1.50, 21.00, 40.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(2, 30.00,  4.00, 22.00, 42.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(2, 45.00,  6.30, 22.50, 44.00, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(2, 60.00,  8.50, 23.00, 46.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(2, 68.00,  9.80, 23.50, 48.00, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
-- Poubelle C (très remplie, humidité élevée)
(3, 50.00,  7.00, 20.00, 60.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(3, 65.00,  9.20, 21.00, 65.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(3, 78.00, 11.00, 22.00, 70.00, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(3, 88.00, 13.50, 23.00, 78.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(3, 95.00, 16.50, 24.00, 85.00, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
-- Poubelle D (peu remplie)
(4,  5.00,  0.80, 19.00, 35.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(4, 15.00,  2.10, 20.00, 37.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(4, 25.00,  3.50, 20.50, 38.00, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(4, 35.00,  5.00, 21.00, 40.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(4, 42.00,  6.00, 21.50, 42.00, DATE_SUB(NOW(), INTERVAL  2 HOUR)),
-- Poubelle E (température et humidité élevées)
(5, 30.00,  4.20, 25.00, 70.00, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(5, 55.00,  7.50, 30.00, 75.00, DATE_SUB(NOW(), INTERVAL 36 HOUR)),
(5, 71.00, 10.00, 35.00, 80.00, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(5, 80.00, 11.80, 38.00, 82.00, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(5, 90.00, 14.00, 42.00, 88.00, DATE_SUB(NOW(), INTERVAL  2 HOUR));

-- Alertes de test
INSERT INTO alertes (id_poubelle, type_alerte, message, statut) VALUES
                                                                    (1, 'pleine',      'Niveau de remplissage à 85%', 'active'),
                                                                    (3, 'critique',    'Niveau critique à 95%',        'active'),
                                                                    (3, 'surcharge',   'Poids élevé : 16.5 kg',        'active'),
                                                                    (3, 'humidite',    'Humidité élevée : 85%',         'active'),
                                                                    (5, 'pleine',      'Niveau de remplissage à 90%',  'active'),
                                                                    (5, 'temperature', 'Température élevée : 42°C',    'active'),
                                                                    (5, 'humidite',    'Humidité élevée : 88%',         'active');