<?php
// ============================================
// Smart Trash - API Poubelles
// GET    /api/poubelles.php         → Liste des poubelles
// GET    /api/poubelles.php?id=1    → Détail d'une poubelle
// POST   /api/poubelles.php         → Ajouter une poubelle
// PUT    /api/poubelles.php?id=1    → Modifier une poubelle
// DELETE /api/poubelles.php?id=1    → Supprimer une poubelle
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fonctions.php';

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    reponseJSON("success", null, 200);
}

// ============================================
// GET : Liste des poubelles ou détail d'une poubelle
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if ($id) {
        // Détail d'une poubelle avec sa dernière mesure
        $sql = "SELECT p.*, 
                       m.niveau AS dernier_niveau, 
                       m.poids AS dernier_poids, 
                       m.temperature AS derniere_temperature,
                       m.date_mesure AS derniere_mesure
                FROM poubelles p
                LEFT JOIN mesures m ON m.id = (
                    SELECT MAX(id) FROM mesures WHERE id_poubelle = p.id
                )
                WHERE p.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $poubelle = $stmt->fetch();

        if (!$poubelle) {
            reponseJSON("error", "Poubelle introuvable", 404);
        }

        reponseJSON("success", $poubelle);

    } else {
        // Liste de toutes les poubelles avec dernière mesure
        $sql = "SELECT p.*, 
                       m.niveau AS dernier_niveau, 
                       m.poids AS dernier_poids, 
                       m.temperature AS derniere_temperature,
                       m.date_mesure AS derniere_mesure
                FROM poubelles p
                LEFT JOIN mesures m ON m.id = (
                    SELECT MAX(id) FROM mesures WHERE id_poubelle = p.id
                )
                ORDER BY p.id";
        $stmt = $pdo->query($sql);
        $poubelles = $stmt->fetchAll();

        reponseJSON("success", $poubelles);
    }
}

// ============================================
// POST : Ajouter une nouvelle poubelle
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = recupererJSON();

    // Vérifier les champs obligatoires
    if (empty($data['nom']) || !isset($data['latitude']) || !isset($data['longitude'])) {
        reponseJSON("error", "Champs requis : nom, latitude, longitude", 400);
    }

    $sql = "INSERT INTO poubelles (nom, adresse, latitude, longitude, statut) 
            VALUES (:nom, :adresse, :latitude, :longitude, :statut)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nom'       => trim($data['nom']),
        'adresse'   => isset($data['adresse']) ? trim($data['adresse']) : null,
        'latitude'  => floatval($data['latitude']),
        'longitude' => floatval($data['longitude']),
        'statut'    => isset($data['statut']) ? $data['statut'] : 'actif'
    ]);

    reponseJSON("success", [
        "message" => "Poubelle ajoutée",
        "id" => $pdo->lastInsertId()
    ], 201);
}

// ============================================
// PUT : Modifier une poubelle
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$id) {
        reponseJSON("error", "ID requis dans l'URL (?id=...)", 400);
    }

    $data = recupererJSON();

    // Vérifier que la poubelle existe
    $stmt = $pdo->prepare("SELECT id FROM poubelles WHERE id = :id");
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        reponseJSON("error", "Poubelle introuvable", 404);
    }

    $sql = "UPDATE poubelles SET 
                nom = :nom, 
                adresse = :adresse, 
                latitude = :latitude, 
                longitude = :longitude, 
                statut = :statut 
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nom'       => trim($data['nom']),
        'adresse'   => isset($data['adresse']) ? trim($data['adresse']) : null,
        'latitude'  => floatval($data['latitude']),
        'longitude' => floatval($data['longitude']),
        'statut'    => isset($data['statut']) ? $data['statut'] : 'actif',
        'id'        => $id
    ]);

    reponseJSON("success", ["message" => "Poubelle modifiée"]);
}

// ============================================
// DELETE : Supprimer une poubelle
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$id) {
        reponseJSON("error", "ID requis dans l'URL (?id=...)", 400);
    }

    $stmt = $pdo->prepare("DELETE FROM poubelles WHERE id = :id");
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        reponseJSON("error", "Poubelle introuvable", 404);
    }

    reponseJSON("success", ["message" => "Poubelle supprimée"]);
}

reponseJSON("error", "Méthode non autorisée", 405);
