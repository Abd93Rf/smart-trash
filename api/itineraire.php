<?php
// ============================================
// Smart Trash - API Itinéraire Optimisé
// GET /api/itineraire.php
// 
// Calcule l'itinéraire optimal pour collecter
// les poubelles dont le niveau dépasse 70%.
// Utilise l'algorithme du plus proche voisin
// (Nearest Neighbor) avec la distance Haversine.
// ============================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fonctions.php';

verifierMethode("GET");

// ============================================
// Étape 1 : Récupérer les poubelles à collecter (> 70%)
// ============================================
$sql = "SELECT p.id, p.nom, p.adresse, p.latitude, p.longitude, m.niveau
        FROM poubelles p
        JOIN mesures m ON m.id = (
            SELECT MAX(id) FROM mesures WHERE id_poubelle = p.id
        )
        WHERE p.statut = 'actif' AND m.niveau > 70
        ORDER BY m.niveau DESC";

$stmt = $pdo->query($sql);
$poubelles = $stmt->fetchAll();

// Si aucune poubelle à collecter
if (count($poubelles) === 0) {
    reponseJSON("success", [
        "message" => "Aucune poubelle à collecter (toutes en dessous de 70%)",
        "distance_totale" => 0,
        "temps_estime" => 0,
        "ordre_passage" => []
    ]);
}

// ============================================
// Étape 2 : Algorithme du plus proche voisin
// ============================================

// On part de la première poubelle de la liste
$aVisiter = $poubelles;        // Poubelles restantes
$ordrePassage = [];             // Résultat : ordre optimisé
$distanceTotale = 0;            // Distance totale en km

// Commencer par la poubelle la plus remplie (déjà triée DESC)
$actuelle = array_shift($aVisiter);
$ordrePassage[] = [
    "id"        => intval($actuelle['id']),
    "nom"       => $actuelle['nom'],
    "adresse"   => $actuelle['adresse'],
    "latitude"  => floatval($actuelle['latitude']),
    "longitude" => floatval($actuelle['longitude']),
    "niveau"    => floatval($actuelle['niveau'])
];

// Tant qu'il reste des poubelles à visiter
while (count($aVisiter) > 0) {

    $distanceMin = PHP_FLOAT_MAX;
    $indexProche = 0;

    // Trouver la poubelle la plus proche
    for ($i = 0; $i < count($aVisiter); $i++) {
        $distance = distanceHaversine(
            $actuelle['latitude'], $actuelle['longitude'],
            $aVisiter[$i]['latitude'], $aVisiter[$i]['longitude']
        );

        if ($distance < $distanceMin) {
            $distanceMin = $distance;
            $indexProche = $i;
        }
    }

    // Ajouter la distance parcourue
    $distanceTotale += $distanceMin;

    // Passer à la poubelle suivante
    $actuelle = $aVisiter[$indexProche];
    array_splice($aVisiter, $indexProche, 1);

    $ordrePassage[] = [
        "id"        => intval($actuelle['id']),
        "nom"       => $actuelle['nom'],
        "adresse"   => $actuelle['adresse'],
        "latitude"  => floatval($actuelle['latitude']),
        "longitude" => floatval($actuelle['longitude']),
        "niveau"    => floatval($actuelle['niveau'])
    ];
}

// ============================================
// Étape 3 : Calculer le temps estimé
// ============================================
// Vitesse moyenne d'un camion en ville : 30 km/h
// Temps d'arrêt par poubelle : 5 minutes
$vitesseMoyenne = 30;
$tempsArretMinutes = 5;
$tempsTrajetMinutes = ($distanceTotale / $vitesseMoyenne) * 60;
$tempsTotal = round($tempsTrajetMinutes + (count($ordrePassage) * $tempsArretMinutes));

// ============================================
// Réponse
// ============================================
reponseJSON("success", [
    "nb_poubelles"    => count($ordrePassage),
    "distance_totale" => round($distanceTotale, 2),
    "temps_estime"    => $tempsTotal,
    "unite_distance"  => "km",
    "unite_temps"     => "minutes",
    "ordre_passage"   => $ordrePassage
]);
