<?php
require_once 'config/database.php';

// Traiter les actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter'])) {
        $stmt = $pdo->prepare("INSERT INTO poubelles (nom, latitude, longitude, statut) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['nom'], $_POST['latitude'], $_POST['longitude'], $_POST['statut']]);
        header('Location: admin.php?success=added');
        exit;
    }
    
    if (isset($_POST['supprimer'])) {
        $stmt = $pdo->prepare("DELETE FROM poubelles WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: admin.php?success=deleted');
        exit;
    }
}

// Récupérer toutes les poubelles
$stmt = $pdo->query("
    SELECT 
        p.*,
        m.niveau,
        m.poids,
        m.temperature
    FROM poubelles p
    LEFT JOIN mesures m ON m.id_poubelle = p.id
    WHERE m.id = (
        SELECT id 
        FROM mesures 
        WHERE id_poubelle = p.id
        ORDER BY date_mesure DESC
        LIMIT 1
    ) OR m.id IS NULL
    ORDER BY p.id ASC
");
$poubelles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Smart Trash</title>
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
            <a href="alertes.php" class="sidebar-item">
                <span class="sidebar-icon"><i class="fas fa-bell"></i></span>
                <span class="sidebar-text">Alertes</span>
            </a>
            <a href="admin.php" class="sidebar-item active">
                <span class="sidebar-icon"><i class="fas fa-cog"></i></span>
                <span class="sidebar-text">Admin</span>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="header">
            <div>
                <h1 class="header-title">Administration</h1>
                <p class="header-time"><i class="fas fa-cogs"></i> Gestion des poubelles</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Ajouter
                </button>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Opération réussie !</span>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Liste des Poubelles</h3>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Position GPS</th>
                        <th>Niveau</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($poubelles as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['nom']); ?></td>
                            <td><?php echo $p['latitude']; ?>, <?php echo $p['longitude']; ?></td>
                            <td><?php echo $p['niveau'] ?? 'N/A'; ?>%</td>
                            <td>
                                <?php if ($p['statut'] === 'Actif'): ?>
                                    <span class="badge badge-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer ?');">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="supprimer" class="btn btn-secondary btn-icon">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modal" class="modal">
        <div class="modal-content">
            <h2>Ajouter une Poubelle</h2>
            <form method="POST">
                <input type="text" name="nom" placeholder="Nom" required class="form-input">
                <input type="number" name="latitude" step="0.000001" placeholder="Latitude" required class="form-input">
                <input type="number" name="longitude" step="0.000001" placeholder="Longitude" required class="form-input">
                <select name="statut" required class="form-input">
                    <option value="Actif">Actif</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" name="ajouter" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 16px;
            font-family: inherit;
        }
    </style>

    <script>
        function openModal() {
            document.getElementById('modal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
    </script>
</body>
</html>
