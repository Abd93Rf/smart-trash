<?php
// ============================================
// Smart Trash - API Mesures
// POST /api/mesures.php → Recevoir données capteurs
// GET  /api/mesures.php → Lire les mesures
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

    // Vérifier que la poubelle existe
    $stmt = $pdo->prepare("SELECT id FROM poubelles WHERE id = :id");
    $stmt->execute(['id' => $idPoubelle]);
    if (!$stmt->fetch()) {
        reponseJSON("error", "Poubelle introuvable", 404);
    }

    // Insérer la mesure dans la base
    $sql = "INSERT INTO mesures (id_poubelle, niveau, poids, temperature) 
            VALUES (:id_poubelle, :niveau, :poids, :temperature)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_poubelle' => $idPoubelle,
        'niveau'      => $niveau,
        'poids'       => $poids,
        'temperature' => $temperature
    ]);

    // Si le niveau dépasse 70%, créer une alerte automatiquement
    if ($niveau > 70) {
        $sqlAlerte = "INSERT INTO alertes (id_poubelle, type_alerte, message, statut)
                      VALUES (:id, 'pleine', :message, 'active')";
        $stmtAlerte = $pdo->prepare($sqlAlerte);
        $stmtAlerte->execute([
            'id'      => $idPoubelle,
            'message' => "Niveau de remplissage à " . round($niveau) . "%"
        ]);
    }

    // Si la température dépasse 40°C, créer une alerte température
    if ($temperature > 40) {
        $sqlAlerte = "INSERT INTO alertes (id_poubelle, type_alerte, message, statut)
                      VALUES (:id, 'temperature', :message, 'active')";
        $stmtAlerte = $pdo->prepare($sqlAlerte);
        $stmtAlerte->execute([
            'id'      => $idPoubelle,
            'message' => "Température élevée : " . round($temperature, 1) . "°C"
        ]);
    }

    reponseJSON("success", ["message" => "Mesure enregistrée", "id" => $pdo->lastInsertId()], 201);
}

// ============================================
// GET : Récupérer les mesures
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Paramètres optionnels
    $idPoubelle = isset($_GET['id_poubelle']) ? intval($_GET['id_poubelle']) : null;
    $limite     = isset($_GET['limite']) ? intval($_GET['limite']) : 50;

    if ($idPoubelle) {
        // Mesures d'une poubelle spécifique
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
        // Toutes les mesures récentes
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

// Si autre méthode
reponseJSON("error", "Méthode non autorisée", 405);
