<?php
// ============================================
// Smart Trash - API Mesures
// POST /api/mesures.php → Recevoir données capteurs
// GET  /api/mesures.php → Lire les mesures
//
// Alertes multi-critères :
//   - Niveau > 70%   → alerte "pleine"
//   - Niveau > 90%   → alerte "critique"
//   - Poids > 15 kg  → alerte "surcharge"
//   - Temp > 40°C    → alerte "temperature"
//   - Humidité > 80% → alerte "humidite"
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fonctions.php';

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    reponseJSON("success", null, 200);
}

// ============================================
// POST : Recevoir les données d'un capteur
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = recupererJSON();

    // Vérifier les champs obligatoires
    if (!isset($data['id_poubelle']) || !isset($data['niveau']) ||
        !isset($data['poids']) || !isset($data['temperature'])) {
        reponseJSON("error", "Champs manquants : id_poubelle, niveau, poids, temperature", 400);
    }

    // Récupérer et valider les valeurs
    $idPoubelle  = intval($data['id_poubelle']);
    $niveau      = floatval($data['niveau']);
    $poids       = floatval($data['poids']);
    $temperature = floatval($data['temperature']);
    $humidite    = isset($data['humidite']) ? floatval($data['humidite']) : null;

    // Vérifier que la poubelle existe
    $stmt = $pdo->prepare("SELECT id FROM poubelles WHERE id = :id");
    $stmt->execute(['id' => $idPoubelle]);
    if (!$stmt->fetch()) {
        reponseJSON("error", "Poubelle introuvable", 404);
    }

    // Insérer la mesure dans la base
    $sql = "INSERT INTO mesures (id_poubelle, niveau, poids, temperature, humidite) 
            VALUES (:id_poubelle, :niveau, :poids, :temperature, :humidite)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_poubelle' => $idPoubelle,
        'niveau'      => $niveau,
        'poids'       => $poids,
        'temperature' => $temperature,
        'humidite'    => $humidite
    ]);

    // ============================================
    // Alertes multi-critères
    // ============================================

    // Alerte niveau > 90% (critique)
    if ($niveau > 90) {
        creerAlerte($pdo, $idPoubelle, 'critique', "Niveau critique à " . round($niveau) . "%");
    }
    // Alerte niveau > 70% (pleine)
    else if ($niveau > 70) {
        creerAlerte($pdo, $idPoubelle, 'pleine', "Niveau de remplissage à " . round($niveau) . "%");
    }

    // Alerte surcharge (poids > 15 kg)
    if ($poids > 15) {
        creerAlerte($pdo, $idPoubelle, 'surcharge', "Poids élevé : " . round($poids, 1) . " kg");
    }

    // Alerte température (> 40°C)
    if ($temperature > 40) {
        creerAlerte($pdo, $idPoubelle, 'temperature', "Température élevée : " . round($temperature, 1) . "°C");
    }

    // Alerte humidité (> 80%)
    if ($humidite !== null && $humidite > 80) {
        creerAlerte($pdo, $idPoubelle, 'humidite', "Humidité élevée : " . round($humidite, 1) . "%");
    }

    reponseJSON("success", ["message" => "Mesure enregistrée", "id" => $pdo->lastInsertId()], 201);
}

// ============================================
// GET : Récupérer les mesures
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $idPoubelle = isset($_GET['id_poubelle']) ? intval($_GET['id_poubelle']) : null;
    $limite     = isset($_GET['limite']) ? intval($_GET['limite']) : 50;

    if ($idPoubelle) {
        $sql = "SELECT m.*, p.nom AS nom_poubelle 
                FROM mesures m 
                JOIN poubelles p ON m.id_poubelle = p.id 
                WHERE m.id_poubelle = :id 
                ORDER BY m.date_mesure DESC 
                LIMIT :limite";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue('id', $idPoubelle, PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
    } else {
        $sql = "SELECT m.*, p.nom AS nom_poubelle 
                FROM mesures m 
                JOIN poubelles p ON m.id_poubelle = p.id 
                ORDER BY m.date_mesure DESC 
                LIMIT :limite";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
    }

    $stmt->execute();
    $mesures = $stmt->fetchAll();

    reponseJSON("success", $mesures);
}

// ============================================
// Fonction pour créer une alerte
// ============================================
function creerAlerte($pdo, $idPoubelle, $type, $message) {
    $sql = "INSERT INTO alertes (id_poubelle, type_alerte, message, statut)
            VALUES (:id, :type, :message, 'active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id'      => $idPoubelle,
        'type'    => $type,
        'message' => $message
    ]);
}

reponseJSON("error", "Méthode non autorisée", 405);