<?php
// ============================================
// Smart Trash - Fonctions utilitaires
// ============================================

/**
 * Envoyer une réponse JSON au client
 * @param string $status  "success" ou "error"
 * @param mixed  $data    Les données à envoyer
 * @param int    $code    Code HTTP (200, 400, 404, 500...)
 */
function reponseJSON($status, $data, $code = 200) {
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");

    if ($status === "success") {
        echo json_encode(["status" => "success", "data" => $data]);
    } else {
        echo json_encode(["status" => "error", "message" => $data]);
    }
    exit;
}

/**
 * Récupérer les données JSON envoyées dans le body de la requête
 * @return array Les données décodées
 */
function recupererJSON() {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if ($data === null) {
        reponseJSON("error", "Données JSON invalides", 400);
    }

    return $data;
}

/**
 * Calcul de la distance entre deux points GPS (formule de Haversine)
 * @param float $lat1 Latitude du point 1
 * @param float $lon1 Longitude du point 1
 * @param float $lat2 Latitude du point 2
 * @param float $lon2 Longitude du point 2
 * @return float Distance en kilomètres
 */
function distanceHaversine($lat1, $lon1, $lat2, $lon2) {
    $rayonTerre = 6371; // Rayon de la Terre en km

    // Conversion en radians
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    // Formule de Haversine
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $rayonTerre * $c;
}

/**
 * Vérifier que l'utilisateur est connecté (session active)
 * Utilisé pour protéger les endpoints admin
 */
function verifierSession() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        reponseJSON("error", "Non autorisé. Veuillez vous connecter.", 401);
    }
}

/**
 * Vérifier que la méthode HTTP est correcte
 * @param string $methode "GET", "POST", "PUT", "DELETE"
 */
function verifierMethode($methode) {
    // Gérer les requêtes OPTIONS (CORS)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        reponseJSON("success", null, 200);
    }

    if ($_SERVER['REQUEST_METHOD'] !== $methode) {
        reponseJSON("error", "Méthode non autorisée. Utilisez $methode.", 405);
    }
}
