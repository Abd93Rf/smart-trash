<?php
// ============================================
// Smart Trash - API Login
// POST /api/login.php
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fonctions.php';

verifierMethode("POST");

// Récupérer les données envoyées
$data = recupererJSON();

// Vérifier que les champs sont présents
if (empty($data['email']) || empty($data['mot_de_passe'])) {
    reponseJSON("error", "Email et mot de passe requis", 400);
}

$email = trim($data['email']);
$motDePasse = $data['mot_de_passe'];

// Chercher l'utilisateur dans la base
$sql = "SELECT id, nom, email, mot_de_passe, role FROM utilisateurs WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute(['email' => $email]);
$utilisateur = $stmt->fetch();

// Vérifier si l'utilisateur existe
if (!$utilisateur) {
    reponseJSON("error", "Email ou mot de passe incorrect", 401);
}

// Vérifier le mot de passe
if (!password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
    reponseJSON("error", "Email ou mot de passe incorrect", 401);
}

// Créer la session
session_start();
$_SESSION['user_id'] = $utilisateur['id'];
$_SESSION['user_nom'] = $utilisateur['nom'];
$_SESSION['user_role'] = $utilisateur['role'];

// Renvoyer les infos utilisateur (sans le mot de passe)
reponseJSON("success", [
    "id" => $utilisateur['id'],
    "nom" => $utilisateur['nom'],
    "email" => $utilisateur['email'],
    "role" => $utilisateur['role']
]);
