<?php
// ============================================
// Smart Trash — API REST
// POST /api/mesures.php
// Réception des données capteurs + alertes
// automatiques avec anti-doublon (transaction)
// ============================================

require_once 'config/database.php';
require_once 'fonctions.php';

header('Content-Type: application/json');
verifierMethode('POST');

// Récupérer et valider les données JSON
$data = recupererJSON();

if (!isset($data['id_poubelle'], $data['niveau'], $data['poids'],
    $data['temperature'], $data['humidite'])) {
    reponseJSON('error', 'Données manquantes', 400);
}

$id_poubelle = intval($data['id_poubelle']);
$niveau      = floatval($data['niveau']);
$poids       = floatval($data['poids']);
$temperature = floatval($data['temperature']);
$humidite    = floatval($data['humidite']);

// Vérifier que la poubelle existe
$check = $pdo->prepare("SELECT id FROM poubelles WHERE id = :id AND statut = 'actif'");
$check->execute(['id' => $id_poubelle]);
if ($check->rowCount() === 0) {
    reponseJSON('error', 'Poubelle introuvable ou inactive', 404);
}

// ============================================
// Insérer la mesure
// ============================================
$stmt = $pdo->prepare(
    "INSERT INTO mesures (id_poubelle, niveau, poids, temperature, humidite, date_mesure)
     VALUES (:id_poubelle, :niveau, :poids, :temperature, :humidite, NOW())"
);
$stmt->execute([
    'id_poubelle' => $id_poubelle,
    'niveau'      => $niveau,
    'poids'       => $poids,
    'temperature' => $temperature,
    'humidite'    => $humidite
]);

// ============================================
// Créer les alertes automatiques sans doublon
// Utilisation d'une transaction SQL pour éviter
// les race conditions (double déclenchement)
// ============================================

/**
 * Crée une alerte seulement si aucune alerte
 * active du même type n'existe déjà pour
 * cette poubelle (BEGIN / COMMIT / ROLLBACK)
 */
function creerAlerteSansDoublon($pdo, $id_poubelle, $type, $message) {
    $pdo->beginTransaction();
    try {
        // Vérifier l'existence d'une alerte active du même type
        $check = $pdo->prepare(
            "SELECT id FROM alertes
             WHERE  id_poubelle = :id_poubelle
             AND    type_alerte = :type
             AND    statut      = 'active'"
        );
        $check->execute([
            'id_poubelle' => $id_poubelle,
            'type'        => $type
        ]);

        // Créer seulement si aucune alerte active n'existe déjà
        if ($check->rowCount() === 0) {
            $insert = $pdo->prepare(
                "INSERT INTO alertes
                    (id_poubelle, type_alerte, message, statut, date_creation)
                 VALUES
                    (:id_poubelle, :type, :message, 'active', NOW())"
            );
            $insert->execute([
                'id_poubelle' => $id_poubelle,
                'type'        => $type,
                'message'     => $message
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

// ---- Seuils d'alerte ----

// Niveau critique > 90%
if ($niveau > 90) {
    creerAlerteSansDoublon(
        $pdo, $id_poubelle, 'critique',
        "Niveau critique à " . round($niveau) . "%"
    );
}
// Niveau plein > 70%
elseif ($niveau > 70) {
    creerAlerteSansDoublon(
        $pdo, $id_poubelle, 'pleine',
        "Niveau de remplissage à " . round($niveau) . "%"
    );
}

// Surcharge > 15 kg
if ($poids > 15) {
    creerAlerteSansDoublon(
        $pdo, $id_poubelle, 'surcharge',
        "Poids élevé : " . round($poids, 1) . " kg"
    );
}

// Température > 40°C
if ($temperature > 40) {
    creerAlerteSansDoublon(
        $pdo, $id_poubelle, 'temperature',
        "Température élevée : " . round($temperature, 1) . "°C"
    );
}

// Humidité > 80%
if ($humidite > 80) {
    creerAlerteSansDoublon(
        $pdo, $id_poubelle, 'humidite',
        "Humidité élevée : " . round($humidite, 1) . "%"
    );
}

reponseJSON('success', ['message' => 'Mesure enregistrée'], 201);