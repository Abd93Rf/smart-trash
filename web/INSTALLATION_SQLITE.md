# Smart Trash - Installation ULTRA SIMPLE avec SQLite

## ✨ POURQUOI SQLITE ?

- ✅ **Pas de serveur MySQL** à démarrer
- ✅ **Pas de phpMyAdmin** nécessaire
- ✅ **Pas de configuration** compliquée
- ✅ **Tout automatique** au premier lancement
- ✅ Un seul fichier `smart_trash.db` créé automatiquement

---

## 🚀 INSTALLATION EN 2 ÉTAPES

### ÉTAPE 1 : Démarrer Apache uniquement

Dans **XAMPP Control Panel** :
- Cliquer **Start** pour **Apache** ✅ (doit être vert)
- ⚠️ **MySQL n'est PAS nécessaire** (on utilise SQLite !)

---

### ÉTAPE 2 : Copier les fichiers

Copier tous les fichiers du dossier **`web/`** dans :
```
C:\xampp\htdocs\smart-trash\
```

**C'est tout !** 

---

## ✅ TESTER

Ouvrir dans le navigateur :
```
http://localhost/smart-trash/dashboard.php
```

**Au premier lancement :**
- Le fichier `smart_trash.db` sera créé automatiquement
- Les 3 tables seront créées automatiquement
- Les données de test seront insérées automatiquement

---

## 📂 Structure après le premier lancement

```
C:\xampp\htdocs\smart-trash\
├── config\
│   └── database.php          ← Gère SQLite automatiquement
├── assets\
├── dashboard.php
├── statistiques.php
├── itineraire.php
├── alertes.php
├── admin.php
└── smart_trash.db            ← Créé automatiquement !
```

---

## 🔍 Comment ça fonctionne ?

Exactement comme votre projet C++ :

```
▶️ Ouvrir dashboard.php
  └─ require 'config/database.php'
       └─ new PDO("sqlite:smart_trash.db")
            └─ PRAGMA foreign_keys = ON
            └─ initSchema()
                 └─ CREATE TABLE IF NOT EXISTS → 3 tables créées
                 └─ SELECT COUNT(*) → vérifie si vide
                 └─ INSERT → données de test insérées si vide
```

Le fichier `smart_trash.db` apparaît automatiquement après le premier lancement !

---

## 📊 Tables créées automatiquement

```sql
CREATE TABLE poubelles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    statut TEXT DEFAULT 'Actif',
    date_creation TEXT DEFAULT (datetime('now'))
);

CREATE TABLE mesures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_poubelle INTEGER NOT NULL,
    niveau INTEGER NOT NULL CHECK(niveau >= 0 AND niveau <= 100),
    poids REAL NOT NULL,
    temperature REAL NOT NULL,
    date_mesure TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
);

CREATE TABLE alertes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_poubelle INTEGER NOT NULL,
    type_alerte TEXT DEFAULT 'Pleine',
    statut TEXT DEFAULT 'Active',
    date_creation TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (id_poubelle) REFERENCES poubelles(id) ON DELETE CASCADE
);
```

---

## 💾 Données de test insérées automatiquement

- **5 poubelles** avec coordonnées GPS
- **5 mesures** avec niveaux variés (35% à 92%)
- **2 alertes** actives pour les poubelles pleines

Ces données ne sont insérées **qu'une seule fois** — si la table n'est pas vide, l'insertion est ignorée.

---

## 🔗 Relations entre tables

```
poubelles
    │
    │ id_poubelle (ON DELETE CASCADE)
    ├──> mesures
    │
    └──> alertes
```

`ON DELETE CASCADE` signifie que supprimer une poubelle supprime automatiquement ses mesures et alertes.

---

## 🛠️ Visualiser la base dans VS Code / PHPStorm

### Extension SQLite Viewer

1. Installer l'extension **SQLite Viewer**
2. Ouvrir `smart_trash.db`
3. Voir les tables et données directement !

### DB Browser for SQLite (recommandé)

1. Télécharger : https://sqlitebrowser.org/
2. Ouvrir `smart_trash.db`
3. Voir/Modifier les tables facilement

---

## 🎯 Pages disponibles

Toutes les pages fonctionnent immédiatement :

| Page | URL |
|------|-----|
| Dashboard | `http://localhost/smart-trash/dashboard.php` |
| Statistiques | `http://localhost/smart-trash/statistiques.php` |
| Itinéraire | `http://localhost/smart-trash/itineraire.php` |
| Alertes | `http://localhost/smart-trash/alertes.php` |
| Admin | `http://localhost/smart-trash/admin.php` |

---

## ⚡ Avantages de SQLite

✅ **Simplicité** : Un seul fichier, pas de serveur
✅ **Portable** : Copiez le fichier .db, c'est tout
✅ **Rapide** : Parfait pour des projets petits/moyens
✅ **Zéro configuration** : Tout est automatique
✅ **Compatible** : Fonctionne partout (Windows, Linux, Mac)

---

## 🔄 Réinitialiser la base

Pour recommencer à zéro :
1. Supprimer le fichier `smart_trash.db`
2. Recharger n'importe quelle page
3. La base sera recréée avec les données de test

---

## ❗ Problèmes courants

### Page blanche ?
- Vérifier qu'Apache est démarré (vert)
- Vérifier que les fichiers sont dans `C:\xampp\htdocs\smart-trash\`

### Erreur SQLite ?
- Vérifier que PHP a l'extension SQLite activée
- Dans `C:\xampp\php\php.ini`, vérifier : `extension=sqlite3`

### Fichier .db non créé ?
- Vérifier les permissions du dossier `smart-trash\`
- Le dossier doit être accessible en écriture
