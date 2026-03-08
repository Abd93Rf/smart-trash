<?php
// ============================================
// Smart Trash - API Alertes
// GET  /api/alertes.php           → Liste des alertes
// GET  /api/alertes.php?statut=active → Filtrer par statut
// PUT  /api/alertes.php?id=1      → Résoudre une alerte
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fonctions.php';

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    reponseJSON("success", null, 200);
}

// ============================================
// GET : Liste des alertes
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $statut = isset($_GET['statut']) ? $_GET['statut'] : null;

    if ($statut) {
        // Filtrer par statut (active ou resolue)
        $sql = "SELECT a.*, p.nom AS nom_poubelle, p.adresse
                FROM alertes a 
                JOIN poubelles p ON a.id_poubelle = p.id 
                WHERE a.statut = :statut
                ORDER BY a.date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['statut' => $statut]);
    } else {
        // Toutes les alertes
        $sql = "SELECT a.*, p.nom AS nom_poubelle, p.adresse
                FROM alertes a 
                JOIN poubelles p ON a.id_poubelle = p.id 
                ORDER BY a.date_creation DESC";
        $stmt = $pdo->query($sql);
    }

    $alertes = $stmt->fetchAll();
    reponseJSON("success", $alertes);
}

// ============================================
// PUT : Résoudre une alerte
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$id) {
        reponseJSON("error", "ID de l'alerte requis (?id=...)", 400);
    }

    // Mettre à jour le statut et la date de résolution
    $sql = "UPDATE alertes SET statut = 'resolue', date_resolution = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        reponseJSON("error", "Alerte introuvable", 404);
    }

    reponseJSON("success", ["message" => "Alerte résolue"]);
}

reponseJSON("error", "Méthode non autorisée", 405);
