<?php
// ============================================
// Smart Trash - API Statistiques
// GET /api/statistiques.php
// Paramètres optionnels :
//   ?type=moyennes       → Moyennes par jour
//   ?type=heures_pointe  → Remplissage par heure
//   ?type=classement     → Poubelles les plus utilisées
//   (sans paramètre)     → Résumé global
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fonctions.php';

verifierMethode("GET");

$type = isset($_GET['type']) ? $_GET['type'] : 'global';

// ============================================
// Résumé global (dashboard)
// ============================================
if ($type === 'global') {

    // Nombre total de poubelles
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM poubelles");
    $totalPoubelles = $stmt->fetch()['total'];

    // Nombre de poubelles actives
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM poubelles WHERE statut = 'actif'");
    $poubellesActives = $stmt->fetch()['total'];

    // Nombre d'alertes actives
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM alertes WHERE statut = 'active'");
    $alertesActives = $stmt->fetch()['total'];

    // Niveau moyen de remplissage (dernière mesure de chaque poubelle)
    $sql = "SELECT AVG(m.niveau) AS moyenne 
            FROM mesures m
            WHERE m.id = (SELECT MAX(id) FROM mesures WHERE id_poubelle = m.id_poubelle)";
    $stmt = $pdo->query($sql);
    $niveauMoyen = round($stmt->fetch()['moyenne'], 1);

    // Poubelles au-dessus de 70%
    $sql = "SELECT COUNT(DISTINCT m.id_poubelle) AS total 
            FROM mesures m
            WHERE m.id = (SELECT MAX(id) FROM mesures WHERE id_poubelle = m.id_poubelle)
            AND m.niveau > 70";
    $stmt = $pdo->query($sql);
    $poubellesAColleter = $stmt->fetch()['total'];

    reponseJSON("success", [
        "total_poubelles"      => intval($totalPoubelles),
        "poubelles_actives"    => intval($poubellesActives),
        "alertes_actives"      => intval($alertesActives),
        "niveau_moyen"         => floatval($niveauMoyen),
        "poubelles_a_collecter" => intval($poubellesAColleter)
    ]);
}

// ============================================
// Moyennes par jour (7 derniers jours)
// ============================================
if ($type === 'moyennes') {

    $sql = "SELECT DATE(date_mesure) AS jour, 
                   ROUND(AVG(niveau), 1) AS moyenne_niveau,
                   ROUND(AVG(poids), 1) AS moyenne_poids,
                   ROUND(AVG(temperature), 1) AS moyenne_temperature,
                   COUNT(*) AS nb_mesures
            FROM mesures 
            WHERE date_mesure >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(date_mesure)
            ORDER BY jour";
    $stmt = $pdo->query($sql);
    $moyennes = $stmt->fetchAll();

    reponseJSON("success", $moyennes);
}

// ============================================
// Heures de pointe
// ============================================
if ($type === 'heures_pointe') {

    $sql = "SELECT HOUR(date_mesure) AS heure, 
                   ROUND(AVG(niveau), 1) AS moyenne_niveau,
                   COUNT(*) AS nb_mesures
            FROM mesures 
            WHERE date_mesure >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY HOUR(date_mesure)
            ORDER BY heure";
    $stmt = $pdo->query($sql);
    $heures = $stmt->fetchAll();

    reponseJSON("success", $heures);
}

// ============================================
// Classement des poubelles les plus utilisées
// ============================================
if ($type === 'classement') {

    $sql = "SELECT p.id, p.nom, 
                   ROUND(AVG(m.niveau), 1) AS moyenne_niveau,
                   COUNT(DISTINCT a.id) AS nb_alertes,
                   MAX(m.niveau) AS niveau_max
            FROM poubelles p
            LEFT JOIN mesures m ON p.id = m.id_poubelle
            LEFT JOIN alertes a ON p.id = a.id_poubelle
            GROUP BY p.id, p.nom
            ORDER BY moyenne_niveau DESC";
    $stmt = $pdo->query($sql);
    $classement = $stmt->fetchAll();

    reponseJSON("success", $classement);
}

reponseJSON("error", "Type de statistique inconnu. Utilisez : global, moyennes, heures_pointe, classement", 400);
