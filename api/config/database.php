<?php
// ============================================
// Smart Trash - Connexion à la base de données
// ============================================

// Paramètres de connexion
$host = "127.0.0.1";
$dbname = "smart_trash";
$username = "root";
$password = "password";

try {
    // Connexion avec PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    // En cas d'erreur, on renvoie un JSON d'erreur
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Erreur de connexion à la base de données"
    ]);
    exit;
}
