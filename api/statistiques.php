<?php
// ============================================
// Smart Trash - API Statistiques
// GET /api/statistiques.php
// Paramètres :
//   ?type=global       → Résumé dashboard
//   ?type=moyennes     → Moyennes par jour
//   ?type=heures_pointe → Remplissage par heure
//   ?type=classement   → Classement enrichi
//   (sans paramètre)   → Résumé global
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fonctions.php';

verifierMethode("GET");

$type = isset($_GET['type']) ? $_GET['type'] : 'global';

// ============================================
// Résumé global (dashboard)
// ============================================
if ($type === 'global') {

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM poubelles");
    $totalPoubelles = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM poubelles WHERE statut = 'actif'");
    $poubellesActives = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM alertes WHERE statut = 'active'");
    $alertesActives = $stmt->fetch()['total'];

    $sql = "SELECT AVG(m.niveau) AS moyenne 
            FROM mesures m
            WHERE m.id = (SELECT MAX(id) FROM mesures WHERE id_poubelle = m.id_poubelle)";
    $stmt = $pdo->query($sql);
    $niveauMoyen = round($stmt->fetch()['moyenne'], 1);

    $sql = "SELECT COUNT(DISTINCT m.id_poubelle) AS total 
        FROM mesures m
        JOIN poubelles p ON m.id_poubelle = p.id
        WHERE m.id = (SELECT MAX(id) FROM mesures WHERE id_poubelle = m.id_poubelle)
        AND m.niveau > 70
        AND p.statut = 'actif'";
    $stmt = $pdo->query($sql);
    $poubellesAColleter = $stmt->fetch()['total'];

    reponseJSON("success", [
        "total_poubelles"       => intval($totalPoubelles),
        "poubelles_actives"     => intval($poubellesActives),
        "alertes_actives"       => intval($alertesActives),
        "niveau_moyen"          => floatval($niveauMoyen),
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
                   ROUND(AVG(humidite), 1) AS moyenne_humidite,
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
// Classement enrichi des poubelles
// ============================================
if ($type === 'classement') {

    $sql = "SELECT p.id, p.nom, 
                   ROUND(AVG(m.niveau), 1) AS moyenne_niveau,
                   ROUND(AVG(m.poids), 1) AS moyenne_poids,
                   ROUND(AVG(m.temperature), 1) AS moyenne_temperature,
                   ROUND(AVG(m.humidite), 1) AS moyenne_humidite,
                   MAX(m.niveau) AS niveau_max,
                   MAX(m.poids) AS poids_max,
                   COUNT(DISTINCT a.id) AS nb_alertes
            FROM poubelles p
            LEFT JOIN mesures m ON p.id = m.id_poubelle
            LEFT JOIN alertes a ON p.id = a.id_poubelle
            GROUP BY p.id, p.nom
            ORDER BY moyenne_niveau DESC";
    $stmt = $pdo->query($sql);
    $classement = $stmt->fetchAll();

    // Calculer la vitesse de remplissage pour chaque poubelle
    foreach ($classement as &$poubelle) {
        $sqlVitesse = "SELECT 
                          TIMESTAMPDIFF(HOUR, MIN(date_mesure), MAX(date_mesure)) AS heures,
                          MAX(niveau) - MIN(niveau) AS progression
                       FROM mesures 
                       WHERE id_poubelle = :id 
                       AND date_mesure >= DATE_SUB(NOW(), INTERVAL 48 HOUR)";
        $stmtV = $pdo->prepare($sqlVitesse);
        $stmtV->execute(['id' => $poubelle['id']]);
        $vitesse = $stmtV->fetch();

        if ($vitesse['heures'] > 0) {
            $poubelle['vitesse_remplissage'] = round($vitesse['progression'] / $vitesse['heures'], 1);
        } else {
            $poubelle['vitesse_remplissage'] = 0;
        }

        // Score combiné (pondération des critères)
        // Plus le score est élevé, plus la poubelle nécessite une attention
        $scoreNiveau = floatval($poubelle['moyenne_niveau']) * 0.4;
        $scorePoids = min(floatval($poubelle['moyenne_poids']) / 20 * 100, 100) * 0.2;
        $scoreTemp = min(floatval($poubelle['moyenne_temperature']) / 50 * 100, 100) * 0.1;
        $scoreHumidite = floatval($poubelle['moyenne_humidite'] ?? 0) * 0.1;
        $scoreAlertes = min(intval($poubelle['nb_alertes']) * 10, 100) * 0.2;

        $poubelle['score'] = round($scoreNiveau + $scorePoids + $scoreTemp + $scoreHumidite + $scoreAlertes, 1);
    }

    // Trier par score décroissant
    usort($classement, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    reponseJSON("success", $classement);
}

reponseJSON("error", "Type de statistique inconnu. Utilisez : global, moyennes, heures_pointe, classement", 400);