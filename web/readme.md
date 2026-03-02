# Smart Trash - Interface Web Moderne avec SQLite

Interface web élégante et intuitive pour le projet Smart Trash - Système Intelligent de Gestion des Déchets Urbains.

## 🎨 Design Moderne

Interface inspirée des meilleures pratiques UI/UX avec :
- Design épuré et professionnel
- Navigation latérale expandable
- Cartes avec effets d'ombre et animations
- Palette écologique (verts, tons neutres)
- Responsive sur tous les appareils

## 📦 Contenu du Package

### Pages Complètes
1. **Dashboard** - Vue d'ensemble avec statistiques live
2. **Statistiques** - Graphiques Chart.js (évolution, distribution, heures de pointe)
3. **Itinéraire** - Carte Leaflet + algorithme d'optimisation Nearest Neighbor
4. **Alertes** - Gestion des poubelles pleines (>70%)
5. **Admin** - CRUD complet pour gérer les poubelles

### Technologies
- **PHP 8 + PDO SQLite** (pas de serveur MySQL !)
- HTML5 / CSS3 moderne
- JavaScript ES6+
- Chart.js 4.4.0
- Leaflet.js 1.9.4
- Font Awesome 6

## 🚀 Installation ULTRA RAPIDE

### 1. Démarrer Apache
Dans XAMPP Control Panel : **Start Apache** (MySQL pas nécessaire !)

### 2. Copier les fichiers
Copier le dossier `web/` dans `C:\xampp\htdocs\smart-trash\`

### 3. Tester
Ouvrir `http://localhost/smart-trash/dashboard.php`

**C'est tout !** La base SQLite se crée automatiquement au premier lancement ! 🎉

## 📊 Fonctionnalités Clés

### Dashboard
- Statistiques en temps réel
- Indicateurs visuels (poubelles actives, alertes, taux moyen)
- Liste des zones de collecte
- Alertes récentes

### Statistiques
- Graphique d'évolution sur 7 jours
- Distribution des états (donut chart)
- Identification des heures de pointe
- Classement des poubelles les plus utilisées

### Itinéraire Optimisé
- Calcul automatique du meilleur parcours
- Carte interactive avec marqueurs numérotés
- Distance totale et temps estimé
- Export vers GPS

### Gestion des Alertes
- Détection automatique (≥70%)
- Classification critique/moyenne
- Résolution en un clic
- Historique complet

### Administration
- Ajout/Modification/Suppression de poubelles
- Tableau de gestion complet
- Modals élégants
- Messages de confirmation

## 💾 Base de données SQLite

**Pourquoi SQLite ?**
- ✅ Pas de serveur MySQL à installer
- ✅ Tout automatique au premier lancement
- ✅ Un seul fichier `smart_trash.db`
- ✅ Portable et simple

**Comment ça fonctionne ?**
```
▶️ Ouvrir dashboard.php
  └─ config/database.php
       └─ new PDO("sqlite:smart_trash.db")
            └─ PRAGMA foreign_keys = ON
            └─ initSchema()
                 └─ CREATE TABLE IF NOT EXISTS
                 └─ INSERT données de test si vide
```

**Tables créées automatiquement :**
- `poubelles` (id, nom, latitude, longitude, statut)
- `mesures` (id_poubelle, niveau, poids, temperature)
- `alertes` (id_poubelle, type_alerte, statut)

## 🎨 Design System

### Couleurs
- Primary: `#2D3436`
- Secondary: `#00B894` (vert écologique)
- Danger: `#D63031` (rouge alertes)
- Warning: `#FDCB6E` (orange)

### Composants
- Cards avec `border-radius: 20px`
- Badges colorés avec icônes
- Progress bars animées
- Modals centrés avec backdrop blur

## 📱 Responsive

- Desktop : Layout complet
- Tablet : Grid adaptatif
- Mobile : Navigation compacte

## 🔐 Sécurité

- Requêtes PDO préparées
- Validation des entrées
- Échappement HTML
- Sessions PHP

## 📝 Structure des Fichiers

```
smart-trash/
├── web/
│   ├── config/
│   │   └── database.php       ← SQLite auto-init
│   ├── assets/css/style.css
│   ├── dashboard.php
│   ├── statistiques.php
│   ├── itineraire.php
│   ├── alertes.php
│   └── admin.php
├── INSTALLATION_SQLITE.md     ← Guide détaillé
└── README.md
```

Après le 1er lancement :
```
web/smart_trash.db             ← Créé automatiquement !
```

---

**Voir INSTALLATION_SQLITE.md pour le guide complet d'installation**
