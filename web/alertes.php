<?php 
require_once 'config/database.php';

// Récupérer les alertes actives
$stmt = $pdo->query("
    SELECT a.*, p.nom, p.latitude, p.longitude, 
           m.niveau, m.poids, m.temperature
    FROM alertes a
    INNER JOIN poubelles p ON a.id_poubelle = p.id
    LEFT JOIN mesures m ON m.id_poubelle = p.id
    WHERE a.statut = 'Active'
    AND m.id = (
        SELECT id 
        FROM mesures 
        WHERE id_poubelle = p.id
        ORDER BY date_mesure DESC
        LIMIT 1
    )
    ORDER BY a.date_creation DESC
");
$alertes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertes - Smart Trash</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-logo"><i class="fas fa-recycle"></i></div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="fas fa-th-large"></i></span>
                <span class="sidebar-text">Dashboard</span>
            </a>
            <a href="statistiques.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="fas fa-chart-line"></i></span>
                <span class="sidebar-text">Statistiques</span>
            </a>
            <a href="itineraire.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="fas fa-route"></i></span>
                <span class="sidebar-text">Itinéraire</span>
            </a>
            <a href="alertes.php" class="sidebar-item active">
                <span class="sidebar-icon"><i class="fas fa-bell"></i></span>
                <span class="sidebar-text">Alertes</span>
            </a>
            <a href="admin.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="fas fa-cog"></i></span>
                <span class="sidebar-text">Admin</span>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="header">
            <div>
                <h1 class="header-title">Gestion des Alertes</h1>
                <p class="header-time">
                    <i class="fas fa-bell"></i>
                    <?php echo count($alertes); ?> alertes actives
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alertes Actives</h3>
            </div>
            <?php if (count($alertes) > 0): ?>
                <div class="list">
                    <?php foreach ($alertes as $alerte): ?>
                        <div class="list-item">
                            <div class="flex gap-2" style="align-items: center; flex: 1;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(214,48,49,0.1); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-trash-alt" style="color: var(--danger); font-size: 20px;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($alerte['nom']); ?>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-secondary);">
                                        Niveau: <?php echo $alerte['niveau']; ?>% • Poids: <?php echo $alerte['poids']; ?> kg • Temp: <?php echo $alerte['temperature']; ?>°C
                                    </div>
                                </div>
                            </div>
                            <span class="badge badge-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $alerte['type_alerte']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="padding: 60px; text-align: center; color: var(--text-secondary);">
                    <i class="fas fa-check-circle" style="font-size: 60px; color: var(--success); display: block; margin-bottom: 20px;"></i>
                    Aucune alerte active
                </p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
