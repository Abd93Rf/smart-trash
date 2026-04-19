# 🗑️ Smart Trash

**Système Intelligent de Gestion des Déchets Urbains**
*Projet de fin d'études – BTS CIEL*

---

## 📋 Sommaire
<<<<<<< HEAD

=======
 
>>>>>>> 9b22e57daf14550864137df254956687955954c8
1. [Présentation du projet](#1-présentation-du-projet)
2. [Objectifs](#2-objectifs)
3. [Architecture globale](#3-architecture-globale)
4. [Partie 1 — Poubelle Connectée (Hardware)](#4-partie-1--poubelle-connectée-hardware)
5. [Partie 2 — API REST PHP + MariaDB](#5-partie-2--api-rest-php--mariadb)
6. [Partie 3 — Base de données](#6-partie-3--base-de-données)
7. [Partie 4 — Analyse & Optimisation](#7-partie-4--analyse--optimisation)
8. [Partie 5 — Site Web](#8-partie-5--site-web)
9. [Sécurité](#9-sécurité)
10. [Structure du projet](#10-structure-du-projet)
11. [Lancement avec Docker](#11-lancement-du-projet-avec-docker)
12. [Tests](#12-tests)
13. [Planning](#13-planning)
14. [Améliorations futures](#14-améliorations-futures)
15. [Résumé technique](#15-résumé-technique)

---

## 1. Présentation du projet

**Smart Trash** est un système IoT permettant d'optimiser la collecte des déchets urbains en collectant uniquement les poubelles réellement nécessaires, au moment optimal.

Le système repose sur :

- **Des capteurs connectés** placés dans chaque poubelle
- **Un microcontrôleur ESP32** avec WiFi intégré
- **Le protocole MQTT** pour la communication temps réel
- **Un serveur Raspberry Pi** hébergeant Docker
- **Une API REST PHP** comme point d'accès unique à la base
- **Une base de données MariaDB**
- **Des scripts Python d'analyse**
- **Un site web de supervision** moderne et responsive

### 🎯 Objectifs principaux

- Réduire les coûts et le nombre de passages inutiles
- Diminuer l'impact environnemental
- Optimiser les itinéraires de collecte
- Fournir des statistiques exploitables pour la planification

---

## 2. Objectifs

### 2.1 Objectifs fonctionnels

- Mesurer le **niveau de remplissage** des poubelles (HC-SR04)
- Mesurer le **poids** des déchets (HX711)
- Mesurer la **température** interne (DHT22)
- Transmettre les données via **MQTT** sur WiFi
- Stocker les données dans **MariaDB** via l'API
- Visualiser les poubelles via un **site web**
- Générer des **alertes automatiques** quand une poubelle dépasse 70%
- Calculer un **itinéraire de collecte optimisé** (plus proche voisin + Haversine)
- Afficher des **statistiques** (quotidien, hebdomadaire, mensuel)

### 2.2 Objectifs non fonctionnels

- Faible consommation énergétique des poubelles connectées
- Architecture modulaire et évolutive
- Sécurité des accès (sessions PHP, hash bcrypt, PDO préparé)
- Interface web responsive (mobile-first)
- Déploiement en une seule commande (Docker Compose)
- Possibilité d'évolution future (app mobile, Machine Learning)

---

## 3. Architecture globale

### Principe clé

Le site web ne se connecte **JAMAIS directement** à la base de données.
**Tout passe par l'API REST PHP**, qui est le seul point d'accès à MariaDB.

### Schéma global

```
┌────────────────────┐
│  POUBELLE (IoT)    │
│  ┌──────────────┐  │
│  │ HC-SR04      │  │  ← Niveau
│  │ HX711        │  │  ← Poids
│  │ DHT22        │  │  ← Température
│  └──────┬───────┘  │
│         ↓          │
│    ESP32 (WiFi)    │
└─────────┬──────────┘
          │ MQTT (JSON)
          ↓
┌──────────────────────────┐
│   RASPBERRY PI (Server)  │
│  ┌────────────────────┐  │
│  │ Broker Mosquitto   │  │
│  └─────────┬──────────┘  │
│            ↓             │
│  ┌────────────────────┐  │
│  │ API REST PHP       │  │
│  │ (point d'accès     │  │
│  │  unique)           │  │
│  └─────────┬──────────┘  │
│            ↓             │
│  ┌────────────────────┐  │
│  │ MariaDB            │  │
│  └────────────────────┘  │
└──────────┬───────────────┘
           │ HTTP (JSON)
           ↓
┌──────────────────────┐
│   SITE WEB (Client)  │
│   HTML/CSS/JS        │
│   fetch() → API      │
└──────────────────────┘
```

### Pourquoi une API REST ?

| Sans API | Avec API REST |
|----------|---------------|
| Chaque page accède directement à la base | Un seul point d'accès centralisé |
| Difficile à maintenir | Facile à faire évoluer |
| Pas réutilisable | Réutilisable (web, mobile, ML, etc.) |
| Sécurité dispersée | Sécurité centralisée |

L'API permet à un **site web**, une **application mobile** ou tout autre client d'utiliser exactement les mêmes données.

### Pourquoi MQTT plutôt que HTTP entre l'ESP32 et le serveur ?

- **Plus léger** : protocole conçu pour l'IoT, faible consommation réseau
- **Asynchrone** : l'ESP32 n'attend pas une réponse, il publie et continue
- **Multi-poubelles** : plusieurs ESP32 peuvent publier en même temps sur le même topic
- **Standard de l'industrie** pour l'IoT

---

## 4. Partie 1 — Poubelle Connectée (Hardware)

### Mission

Collecter et transmettre les données (niveau, poids, température).

### Composants

| Composant | Rôle | Tension |
|-----------|------|---------|
| **ESP32** | Microcontrôleur avec WiFi/Bluetooth intégré | 3.3V |
| **HC-SR04** | Capteur ultrason (niveau de remplissage) | 5V |
| **HX711 + cellule** | Capteur de poids | 3.3-5V |
| **DHT22** | Capteur de température et humidité | 3.3-5V |
| **Batterie LiPo** | Alimentation autonome | 3.7V |

### Fonctionnalités du firmware

1. **Lecture** des 3 capteurs à intervalle configurable
2. **Mode veille** entre les mesures pour économiser la batterie
3. **Connexion WiFi** automatique avec reconnexion en cas de coupure
4. **Publication MQTT** des données au broker Mosquitto
5. **Format JSON** pour les messages

### Exemple de message MQTT publié

**Topic :** `smart-trash/mesures`

```json
{
  "id_poubelle": 1,
  "niveau": 75,
  "poids": 12.4,
  "temperature": 28.5
}
```

### Livrables hardware

- Code ESP32 commenté
- Schéma de câblage
- Prototype fonctionnel
- Guide d'installation matérielle

---

## 5. Partie 2 — API REST PHP + MariaDB

### Mission

L'API PHP est le **cœur du système**. Elle fait le lien entre les capteurs (via le broker MQTT), la base de données et le site web.

### ⚙Ce que fait l'API

- Recevoir les données des capteurs (transmises depuis le broker MQTT)
- Enregistrer les mesures dans MariaDB
- Détecter et créer les alertes (poubelle > 70% ou température > 40°C)
- Calculer les statistiques
- Calculer l'itinéraire de collecte optimisé
- Renvoyer les données au site web en JSON

### 🛠Technologies

- Raspberry Pi 3/4
- PHP 8.2 + PDO
- MariaDB 10.6
- Apache 2 (via image Docker `php:8.2-apache`)
- Broker MQTT Mosquitto

### Liste des endpoints

| Méthode | URL | Rôle |
|---------|-----|------|
| `POST` | `/api/mesures.php` | Recevoir les données capteurs |
| `GET` | `/api/poubelles.php` | Liste des poubelles |
| `POST` | `/api/poubelles.php` | Ajouter une poubelle |
| `PUT` | `/api/poubelles.php?id=X` | Modifier une poubelle |
| `DELETE` | `/api/poubelles.php?id=X` | Supprimer une poubelle |
| `GET` | `/api/statistiques.php` | Moyennes, heures de pointe, classement |
| `GET` | `/api/alertes.php` | Liste des alertes actives |
| `PUT` | `/api/alertes.php?id=X` | Résoudre une alerte |
| `GET` | `/api/itineraire.php` | Itinéraire optimisé |
| `POST` | `/api/login.php` | Connexion utilisateur |

### Connexion à MariaDB (PDO)

```php
$pdo = new PDO(
    "mysql:host=db;dbname=smart_trash;charset=utf8",
    "root",
    "password",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
```

> **Note :** Le host `db` correspond au nom du service MariaDB dans `docker-compose.yml`.

### Format des réponses JSON

**Succès :**

```json
{
  "status": "success",
  "data": [
    { "id": 1, "nom": "Poubelle A", "niveau": 45 },
    { "id": 2, "nom": "Poubelle B", "niveau": 82 }
  ]
}
```

**Erreur :**

```json
{
  "status": "error",
  "message": "Description de l'erreur"
}
```

---

## 6. Partie 3 — Base de données

### Table `utilisateurs`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Identifiant unique |
| `nom` | VARCHAR(100) | Nom de l'utilisateur |
| `email` | VARCHAR(150) | Email (unique) |
| `mot_de_passe` | VARCHAR(255) | Hash bcrypt |
| `role` | ENUM | admin / opérateur |
| `date_creation` | DATETIME | Date de création |

### Table `poubelles`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Identifiant unique |
| `nom` | VARCHAR(100) | Nom / code poubelle |
| `adresse` | VARCHAR(255) | Adresse |
| `latitude` | DECIMAL(10,6) | Coordonnée GPS |
| `longitude` | DECIMAL(10,6) | Coordonnée GPS |
| `statut` | ENUM | actif / maintenance / inactif |
| `date_creation` | DATETIME | Date de création |

### Table `mesures`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Identifiant |
| `id_poubelle` | INT (FK) | Référence vers `poubelles.id` |
| `niveau` | DECIMAL(5,2) | % de remplissage |
| `poids` | DECIMAL(6,2) | Poids en kg |
| `temperature` | DECIMAL(5,2) | Température en °C |
| `date_mesure` | DATETIME | Horodatage |

### Table `alertes`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT (PK) | Identifiant |
| `id_poubelle` | INT (FK) | Référence vers `poubelles.id` |
| `type_alerte` | ENUM | pleine / temperature / maintenance |
| `message` | VARCHAR(255) | Description |
| `statut` | ENUM | active / résolue |
| `date_creation` | DATETIME | Date de création |
| `date_resolution` | DATETIME | Date de résolution |

---

## 7. Partie 4 — Analyse & Optimisation

### Mission

Transformer les données brutes en informations utiles, accessibles via l'API et via des scripts Python autonomes.

### Analyses réalisées

#### Moyennes

Calcul de la moyenne de remplissage par jour, semaine et mois.

```sql
SELECT AVG(niveau) FROM mesures GROUP BY DATE(date_mesure)
```

#### Heures de pointe

Identifier à quelles heures les poubelles se remplissent le plus.

```sql
SELECT HOUR(date_mesure), AVG(niveau) FROM mesures GROUP BY HOUR(date_mesure)
```

#### Classement des poubelles les plus utilisées

Tri par taux moyen de remplissage et nombre d'alertes.

#### Itinéraire de collecte optimisé

1. Sélectionner les poubelles dont le niveau dépasse 70%
2. Récupérer leurs coordonnées GPS
3. Calculer un ordre de passage avec l'**algorithme du plus proche voisin** (Nearest Neighbor)
4. Utiliser la **formule de Haversine** pour calculer les distances réelles
5. Estimer le temps total (vitesse moyenne 30 km/h + 5 min par poubelle)

### Exemple de résultat (itinéraire)

```json
{
  "nb_poubelles": 3,
  "distance_totale": 12.5,
  "temps_estime": 45,
  "ordre_passage": [
    { "id": 3, "nom": "Poubelle C", "latitude": 48.93, "longitude": 2.35, "niveau": 95 },
    { "id": 1, "nom": "Poubelle A", "latitude": 48.94, "longitude": 2.36, "niveau": 85 },
    { "id": 5, "nom": "Poubelle E", "latitude": 48.92, "longitude": 2.36, "niveau": 78 }
  ]
}
```

### Scripts Python d'analyse

En plus de l'API PHP, les analyses sont aussi disponibles sous forme de scripts Python dans le dossier `analyse/`. Cela permet de lancer les calculs en console, indépendamment du site web.

**Installation des dépendances :**

```bash
pip install mysql-connector-python
```

**Utilisation :**

```bash
cd analyse

# Lancer toutes les analyses d'un coup
python main.py

# Ou lancer une analyse spécifique
python moyennes.py
python heures_pointe.py
python classement.py
python itineraire.py
```

| Script | Rôle |
|--------|------|
| `config_db.py` | Connexion à MariaDB |
| `moyennes.py` | Moyennes par jour, semaine et mois |
| `heures_pointe.py` | Détection des heures de pointe |
| `classement.py` | Classement des poubelles les plus utilisées |
| `itineraire.py` | Itinéraire optimisé (Haversine + Nearest Neighbor) |
| `main.py` | Lance toutes les analyses |

---

## 8. Partie 5 — Site Web

### Mission

Afficher les données en appelant l'API. Le site web est un **client** qui ne touche jamais à la base de données directement.

### Technologies

- **HTML5 / CSS3** + **Bootstrap 5** pour le design responsive
- **JavaScript** (vanilla, fetch API)
- **Chart.js** pour les graphiques interactifs
- **Leaflet.js** + **OpenStreetMap** pour la carte
- **Bootstrap Icons** pour les icônes

### Comment le site communique avec l'API

```javascript
// Exemple : récupérer les statistiques
fetch("/api/statistiques.php?type=global")
  .then(response => response.json())
  .then(resultat => {
    if (resultat.status === "success") {
      // Afficher les résultats sur la page
      console.log(resultat.data);
    }
  });
```

### Pages du site

| Page | Description |
|------|-------------|
| **Login** | Connexion utilisateur (email + mot de passe) |
| **Dashboard** | Vue globale, cartes résumé, tableau des poubelles |
| **Statistiques** | Graphiques Chart.js (moyennes, heures de pointe, classement) |
| **Itinéraire** | Carte Leaflet avec ordre de passage optimisé |
| **Alertes** | Liste des alertes actives, filtrage, résolution |
| **Admin** | Ajouter / modifier / supprimer des poubelles (CRUD) |

---

## 9. Sécurité

Le projet applique plusieurs couches de sécurité :

### Authentification

- **Sessions PHP** : `$_SESSION` pour garder l'utilisateur connecté
- **Vérification systématique** sur les endpoints sensibles via `verifierSession()`

### Mots de passe

- **Hash bcrypt** avec `password_hash($motDePasse, PASSWORD_DEFAULT)`
- **Vérification sécurisée** avec `password_verify()`
- Les mots de passe **ne sont jamais stockés en clair**

### Protection contre les injections SQL

- **Requêtes préparées PDO** sur tous les endpoints
- Exemple :

```php
// ❌ DANGEREUX
$sql = "SELECT * FROM utilisateurs WHERE email = '$email'";

// ✅ SÉCURISÉ
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email");
$stmt->execute(['email' => $email]);
```

### Autres mesures

- **Validation des données** côté API avant insertion
- **Accès à MariaDB uniquement via l'API** (pas d'accès direct depuis le front-end)
- **Script `init_password.php`** qui régénère automatiquement le hash bcrypt au démarrage du conteneur (résout les incompatibilités entre versions PHP)

---

## 10. Structure du projet

```
smart-trash/
│
├── esp32/                      ← Code ESP32 + schémas
│   ├── sketch.ino                 ← Code Arduino
│   └── diagram.json               ← Schéma Wokwi
│
├── database/
│   └── schema.sql                 ← Tables + données de test
│
├── api/                        ← Backend (API REST PHP)
│   ├── config/
│   │   └── database.php           ← Connexion PDO sécurisée
│   ├── mesures.php                ← POST : recevoir données capteurs
│   ├── poubelles.php              ← CRUD poubelles
│   ├── statistiques.php           ← Moyennes, heures de pointe
│   ├── itineraire.php             ← Itinéraire optimisé
│   ├── alertes.php                ← Gestion des alertes
│   ├── login.php                  ← Authentification
│   ├── init_password.php          ← Init automatique du mot de passe admin
│   └── fonctions.php              ← Utilitaires (Haversine, JSON, sessions)
│
├── analyse/                    ← Scripts Python d'analyse
│   ├── config_db.py               ← Connexion à MariaDB
│   ├── moyennes.py                ← Moyennes par jour/semaine/mois
│   ├── heures_pointe.py           ← Détection des heures de pointe
│   ├── classement.py              ← Classement des poubelles
│   ├── itineraire.py              ← Itinéraire optimisé
│   └── main.py                    ← Lance toutes les analyses
│
├── web/                        ← Site web (front-end)
│   ├── index.html                 ← Redirection
│   ├── login.html                 ← Page de connexion
│   ├── dashboard.html             ← Vue globale
│   ├── statistiques.html          ← Graphiques
│   ├── itineraire.html            ← Carte et trajet
│   ├── alertes.html               ← Liste des alertes
│   ├── admin.html                 ← Gestion des poubelles
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── auth.js                ← Authentification client
│   │   ├── dashboard.js           ← fetch() vers l'API
│   │   ├── statistiques.js
│   │   ├── itineraire.js
│   │   ├── alertes.js
│   │   └── admin.js
│   └── assets/
│
├── docker-compose.yml          ← Lancement MariaDB + Apache/PHP
├── README.md                   ← Ce fichier
├── GUIDE_PHYSIQUE.md           ← Guide installation matérielle
└── PROBLEMES_FREQUENTS.md      ← FAQ des problèmes rencontrés
```

---

## 11. Lancement du projet avec Docker

### Prérequis

- **Docker** et **Docker Compose** installés
- Pas besoin d'installer PHP, Apache ou MariaDB séparément, **tout est dans Docker**

### Étapes

#### 1. Récupérer le projet

```bash
git clone https://gitea.lasallesaintdenis.com/Smart_Trash/smart-trash.git
cd smart-trash
```

Ou décompresser le zip :

```bash
unzip smart-trash.zip
cd smart-trash
```

#### 2. Lancer le projet (une seule commande !)

```bash
docker-compose up -d
```

Cette commande va :

- ⬇️ Télécharger les images **MariaDB 10.6** et **PHP 8.2 + Apache**
- 🐳 Créer le conteneur `smart_trash_db` (base de données)
- 🐳 Créer le conteneur `smart_trash_web` (serveur web + API)
- 🗃️ Créer la base `smart_trash` automatiquement
- 📥 Exécuter `database/schema.sql` (tables + données de test)
- 🔧 Installer l'extension PDO MySQL automatiquement
- 🔐 Régénérer le hash du mot de passe admin
- 💾 Sauvegarder les données dans `db_data/` (persistance)

#### 3. Accéder au site

🌐 **Site web** : http://localhost:8080/web/login.html  
🔌 **API** : http://localhost:8080/api/poubelles.php

**Identifiants par défaut :**
- Email : `admin@smarttrash.fr`
- Mot de passe : `admin123`

#### 4. Vérifier que tout fonctionne

```bash
# Voir si les conteneurs tournent
docker ps

# Se connecter à MariaDB
docker exec -it smart_trash_db mysql -u root -ppassword smart_trash

# Dans MariaDB :
SHOW TABLES;
SELECT * FROM poubelles;
EXIT;
```

#### 5. Arrêter le projet

```bash
docker-compose down
```

### Commandes utiles

| Commande | Description                            |
|----------|----------------------------------------|
| `docker-compose up -d` | Démarrer tout le projet                |
| `docker-compose down` | Arrêter tout le projet                 |
| `docker-compose logs` | Voir tous les logs                     |
| `docker-compose logs web` | Voir les logs du serveur web           |
| `docker-compose logs db` | Voir les logs de MariaDB             |
| `docker ps` | Vérifier que les conteneurs tournent |

### Réinitialiser la base de données

Pour repartir de zéro :

```bash
docker-compose down
rm -rf db_data
docker-compose up -d
```

---

## 12. Tests

### Tests fonctionnels

- Envoyer des données JSON simulées à l'API (via Postman, curl ou client MQTT)
- Vérifier l'insertion dans MariaDB
- Vérifier la création automatique d'alerte si niveau > 70%
- Tester le calcul de l'itinéraire optimisé
- Tester l'affichage des graphiques et de la carte
- Tester la connexion / déconnexion
- Lancer les scripts Python d'analyse

### Test rapide de l'API

```bash
# Récupérer la liste des poubelles
curl http://localhost:8080/api/poubelles.php

# Envoyer une mesure simulée
curl -X POST http://localhost:8080/api/mesures.php \
  -H "Content-Type: application/json" \
  -d '{"id_poubelle": 1, "niveau": 85, "poids": 12.5, "temperature": 25.3}'
```

---

## 13. Planning

| Semaine | Phase | Tâches | Livrables |
|---------|-------|--------|-----------|
| 1–2 | Préparation | Cahier des charges, architecture, schéma base | Documents validés (Revue 1) |
| 3–4 | API + BDD | Création de la base, développement de l'API PHP | API fonctionnelle, base opérationnelle |
| 5–6 | Site Web | Pages HTML, fetch vers l'API, graphiques, cartes | Site web complet |
| 7 | Analyse | Statistiques, heures de pointe, itinéraire | Endpoints analyse OK |
| 8 | Intégration | Affichage des analyses sur le site | Pages stats + itinéraire OK |
| 9 | Hardware | Capteurs, ESP32, MQTT, tests | Prototype fonctionnel |
| 10 | Tests & Docs | Tests complets, corrections, documentation | Projet prêt pour la soutenance |

---

## 14. Améliorations futures

- **Machine Learning** pour prédire le remplissage et les pics
- **Application mobile** (qui utiliserait la même API)
- **Notifications SMS / Email** pour les alertes critiques
- **Algorithme de tournée plus avancé** (TSP, recherche tabou)
- **Détection d'anomalies** (incendie, surcharge, vandalisme)
- **Multi-villes / Multi-tenants** pour gérer plusieurs collectivités
- **Dashboard temps réel** avec WebSocket / Server-Sent Events

---

## 15. Résumé technique

| Couche | Technologie | Rôle |
|--------|-------------|------|
| **Hardware** | ESP32 + capteurs (HC-SR04, HX711, DHT22) | Collecte des données |
| **Communication** | WiFi + MQTT (Mosquitto) | Transmission au serveur |
| **Déploiement** | Docker Compose (Apache + PHP + MariaDB) | Lancement en une commande |
| **Backend** | PHP 8.2 + PDO (API REST) | Stockage, analyse, réponses |
| **Base de données** | MariaDB 10.6 | Historique des mesures et alertes |
| **Analyse** | PHP + SQL + Python | Statistiques, itinéraire optimisé |
| **Front-end** | HTML / CSS / Bootstrap 5 / JS (fetch) | Affichage (client de l'API) |
| **Visualisation** | Chart.js + Leaflet.js | Graphiques et cartes interactives |
| **Sécurité** | Sessions + bcrypt + PDO préparé | Authentification et protection |

---

## Équipe du projet

| Étudiant | Rôle principal                                  |
|----------|-------------------------------------------------|
| **Enzo** | Capteurs + ESP32 + Docker                       |
| **Abdul** | Serveur Raspberry Pi + BDD |
| **Abd-El-Raouf** | Site web + Sécurisation PDO + Documentation API |
| **Kilian** | WiFi ESP32 + Scripts Python + Optimisation      |

---

## Conclusion

**Smart Trash** est un projet IoT complet pour un BTS CIEL qui démontre la maîtrise de l'ensemble de la chaîne :

- ✅ **De l'embarqué au cloud** (ESP32 → MQTT → API → BDD → Site Web)
- ✅ **Architecture API REST** moderne et évolutive
- ✅ **Déploiement professionnel** avec Docker Compose
- ✅ **Sécurité multi-couches** (sessions, bcrypt, PDO préparé)
- ✅ **Analyse de données** avec Python + visualisations interactives
- ✅ **Code documenté** et maintenable