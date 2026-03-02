<?php
require_once 'config/database.php';

// Récupérer les statistiques
$stmt = $pdo->query("SELECT COUNT(*) as total FROM poubelles WHERE statut = 'Actif'");
$total_poubelles = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM alertes WHERE statut = 'Active'");
$total_alertes = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM mesures m
    INNER JOIN poubelles p ON m.id_poubelle = p.id
    WHERE m.niveau >= 70 AND p.statut = 'Actif'
    AND m.id = (
        SELECT id 
        FROM mesures 
        WHERE id_poubelle = m.id_poubelle
        ORDER BY date_mesure DESC
        LIMIT 1
    )
");
$poubelles_pleines = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT AVG(niveau) as moyenne FROM mesures WHERE DATE(date_mesure) = DATE('now')");
$taux_remplissage = round($stmt->fetch()['moyenne'] ?? 0, 1);

// Récupérer les dernières alertes
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
    LIMIT 5
");
$alertes = $stmt->fetchAll();

// Récupérer les poubelles par statut
$stmt = $pdo->query("
    SELECT 
        p.*,
        m.niveau,
        m.poids,
        m.temperature,
        m.date_mesure
    FROM poubelles p
    LEFT JOIN mesures m ON m.id_poubelle = p.id
    WHERE m.id = (
        SELECT id 
        FROM mesures 
        WHERE id_poubelle = p.id
        ORDER BY date_mesure DESC
        LIMIT 1
    )
    ORDER BY m.niveau DESC
    LIMIT 6
");
$poubelles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Trash</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-recycle"></i>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-item active">
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
            <a href="alertes.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="fas fa-bell"></i></span>
                <span class="sidebar-text">Alertes</span>
            </a>
            <a href="admin.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="fas fa-cog"></i></span>
                <span class="sidebar-text">Admin</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <div>
                <h1 class="header-title">Dashboard</h1>
                <p class="header-time">
                    <i class="far fa-clock"></i>
                    <?php echo date('l, d F Y - H:i'); ?>
                </p>
            </div>
            <div class="header-actions">
                <div class="live-badge">
                    <span class="live-dot"></span>
                    <span>Live</span>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-4 mb-4">
            <div class="card">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_poubelles; ?></div>
                    <div class="stat-label">Poubelles Actives</div>
                    <span class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> 5%
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--danger);"><?php echo $poubelles_pleines; ?></div>
                    <div class="stat-label">Poubelles Pleines</div>
                    <span class="stat-change negative">
                        <i class="fas fa-arrow-up"></i> <?php echo $poubelles_pleines; ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--warning);"><?php echo $total_alertes; ?></div>
                    <div class="stat-label">Alertes Actives</div>
                    <span class="badge badge-warning">
                        <i class="fas fa-exclamation-triangle"></i> Urgent
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--secondary);"><?php echo $taux_remplissage; ?>%</div>
                    <div class="stat-label">Taux Moyen</div>
                    <div class="progress mt-2">
                        <div class="progress-bar" style="width: <?php echo $taux_remplissage; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-2">
            <!-- Live View Card -->
            <div class="card card-dark" style="grid-row: span 2;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Vue en Temps Réel</h3>
                        <p class="card-subtitle">Surveillance des poubelles</p>
                    </div>
                    <div class="badge badge-success">
                        <i class="fas fa-wifi"></i> Connecté
                    </div>
                </div>

                <div style="background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">
                            <i class="fas fa-thermometer-half"></i> 24°C
                        </span>
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">
                            <i class="fas fa-chart-line"></i> 50%
                        </span>
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">
                            <i class="fas fa-battery-three-quarters"></i> 350W
                        </span>
                        <span style="color: rgba(255,255,255,0.7); font-size: 13px;">
                            <i class="fas fa-tint"></i> 80%
                        </span>
                    </div>
                    
                    <div style="height: 200px; display: flex; align-items: center; justify-content: center;">
                        <div style="text-align: center;">
                            <i class="fas fa-trash-alt" style="font-size: 80px; color: var(--secondary); opacity: 0.3;"></i>
                            <p style="margin-top: 20px; color: rgba(255,255,255,0.6);">
                                Système de surveillance actif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="list">
                    <?php if (count($poubelles) > 0): ?>
                        <?php foreach (array_slice($poubelles, 0, 3) as $poubelle): ?>
                            <div class="device-item">
                                <div class="device-icon">
                                    <i class="fas fa-trash-alt" style="color: var(--secondary);"></i>
                                </div>
                                <div class="device-info">
                                    <div class="device-name"><?php echo htmlspecialchars($poubelle['nom']); ?></div>
                                    <div class="device-count">Remplissage: <?php echo $poubelle['niveau']; ?>%</div>
                                </div>
                                <div>
                                    <?php if ($poubelle['niveau'] >= 70): ?>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-exclamation-circle"></i> Pleine
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> OK
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.5); text-align: center; padding: 20px;">
                            Aucune poubelle active
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rooms / Zones -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Zones de Collecte</h3>
                    <a href="admin.php" class="btn btn-secondary btn-icon">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>

                <div class="list">
                    <div class="device-item">
                        <div class="device-icon" style="background: rgba(0,184,148,0.1);">
                            <i class="fas fa-map-marker-alt" style="color: var(--secondary);"></i>
                        </div>
                        <div class="device-info">
                            <div class="device-name">Centre Ville</div>
                            <div class="device-count"><?php echo round($total_poubelles * 0.4); ?> poubelles</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--text-light);"></i>
                    </div>

                    <div class="device-item">
                        <div class="device-icon" style="background: rgba(0,206,201,0.1);">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i>
                        </div>
                        <div class="device-info">
                            <div class="device-name">Zone Industrielle</div>
                            <div class="device-count"><?php echo round($total_poubelles * 0.3); ?> poubelles</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--text-light);"></i>
                    </div>

                    <div class="device-item">
                        <div class="device-icon" style="background: rgba(253,203,110,0.1);">
                            <i class="fas fa-map-marker-alt" style="color: var(--warning);"></i>
                        </div>
                        <div class="device-info">
                            <div class="device-name">Quartier Résidentiel</div>
                            <div class="device-count"><?php echo round($total_poubelles * 0.3); ?> poubelles</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: var(--text-light);"></i>
                    </div>
                </div>

                <button class="btn btn-primary" style="width: 100%; margin-top: 16px;">
                    <i class="fas fa-plus"></i>
                    Ajouter une Zone
                </button>
            </div>

            <!-- Recent Alerts -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Alertes Récentes</h3>
                    <a href="alertes.php" class="btn btn-secondary" style="font-size: 13px;">
                        Voir tout
                    </a>
                </div>

                <div class="list">
                    <?php if (count($alertes) > 0): ?>
                        <?php foreach (array_slice($alertes, 0, 4) as $alerte): ?>
                            <div class="list-item">
                                <div class="flex gap-2" style="align-items: center; flex: 1;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(214,48,49,0.1); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; font-size: 14px;">
                                            <?php echo htmlspecialchars($alerte['nom']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--text-secondary);">
                                            Remplissage: <?php echo $alerte['niveau']; ?>%
                                        </div>
                                    </div>
                                </div>
                                <span class="badge badge-danger">
                                    <?php echo $alerte['type_alerte']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-secondary); text-align: center; padding: 30px;">
                            <i class="fas fa-check-circle" style="font-size: 40px; color: var(--success); margin-bottom: 10px; display: block;"></i>
                            Aucune alerte active
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
