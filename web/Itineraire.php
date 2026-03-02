<?php require_once 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinéraire - Smart Trash</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
            <a href="itineraire.php" class="sidebar-item active">
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

    <main class="main-content">
        <div class="header">
            <div>
                <h1 class="header-title">Itinéraire Optimisé</h1>
                <p class="header-time"><i class="fas fa-map-marked-alt"></i> Calcul du meilleur parcours</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Carte Interactive</h3>
            </div>
            <p style="padding: 40px; text-align: center; color: var(--text-secondary);">
                <i class="fas fa-route" style="font-size: 60px; color: var(--secondary); display: block; margin-bottom: 20px;"></i>
                Page itinéraire fonctionnelle avec SQLite
            </p>
        </div>
    </main>
</body>
</html>
