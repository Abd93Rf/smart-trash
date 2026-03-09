# Smart Trash

**Système Intelligent de Gestion des Déchets Urbains**

---

## 1. Présentation du projet

Smart Trash est un système IoT permettant d'optimiser la collecte des déchets urbains en collectant uniquement les poubelles réellement nécessaires, au moment optimal, grâce à :

- Des capteurs connectés
- La transmission LoRa
- Un serveur Raspberry Pi
- Une API PHP pour accéder à la base de données
- Une base de données MySQL
- Une analyse des données
- Un site web de supervision

### Objectifs principaux

- Réduire les coûts et le nombre de passages inutiles
- Diminuer l'impact environnemental
- Optimiser les itinéraires de collecte
- Fournir des statistiques pour la planification

---

## 2. Objectifs du projet

### 2.1 Objectifs fonctionnels

- Mesurer le niveau de remplissage des poubelles
- Mesurer le poids des déchets
- Mesurer la température interne
- Transmettre les données via LoRa
- Recevoir les données via l'API PHP
- Stocker les données dans MySQL
- Visualiser les poubelles via un site web
- Générer des alertes quand une poubelle dépasse 70%
- Calculer un itinéraire de collecte optimisé
- Afficher des statistiques (quotidien, hebdomadaire, mensuel)

### 2.2 Objectifs non fonctionnels

- Faible consommation énergétique des poubelles connectées
- Architecture simple et évolutive
- Sécurité des accès (sessions PHP)
- Interface web responsive
- Possibilité d'évolution future (app mobile, Machine Learning)

---

## 3. Architecture globale

### Principe

Le site web ne se connecte **pas directement** à la base de données.
Tout passe par l'API PHP, qui est le seul point d'accès à MySQL.

```
[Poubelle Connectée]
 ├─ Capteurs (HC-SR04, HX711, DHT22)
 ├─ Arduino
 └─ Module LoRa
       ↓
[Passerelle LoRa]
       ↓
[Raspberry Pi – Serveur]
 ├─ API PHP (accès unique à MySQL)
 ├─ MySQL
       ↓
[Site Web (HTML/CSS/JS)]
 └─ Appelle l'API avec fetch()
```

### Pourquoi une API ?

Sans API, chaque page PHP accède directement à la base. Si on veut changer quelque chose dans la base ou ajouter une app mobile plus tard, il faut tout modifier.

Avec une API, l'accès à la base est centralisé à un seul endroit. Le site web, une app mobile ou tout autre client peut utiliser la même API. C'est plus propre, plus sécurisé et plus facile à faire évoluer.

---

## 4. Partie 1 – Poubelle Connectée (Hardware)

### Mission

Collecter et transmettre les données (niveau, poids, température).

### Composants

- Arduino UNO / Nano
- HC-SR04 : mesure du niveau de remplissage
- HX711 + cellule de charge : mesure du poids
- DHT22 : mesure de la température interne
- Module LoRa : transmission longue portée

### Fonctionnalités

- Lecture des capteurs toutes les X minutes (configurable)
- Mode veille pour économiser la batterie
- Envoi des données en JSON via LoRa

### Exemple de données envoyées

```json
{
  "id_poubelle": 1,
  "niveau": 75,
  "poids": 12.4,
  "temperature": 28.5
}
```

### Livrables

- Code Arduino commenté
- Schéma de câblage
- Prototype fonctionnel
- Guide d'installation

---

## 5. Partie 2 – API PHP + MySQL (Raspberry Pi)

### Mission

L'API PHP est le cœur du système. Elle fait le lien entre les capteurs, la base de données et le site web.

### Ce que fait l'API

- Recevoir les données des capteurs (via LoRa)
- Enregistrer les mesures dans MySQL
- Détecter les alertes (poubelle > 70%)
- Calculer les statistiques
- Calculer l'itinéraire de collecte
- Renvoyer les données au site web en JSON

### Technologies

- Raspberry Pi 3/4
- PHP 8 + PDO
- MySQL / MariaDB

### Liste des endpoints

| Méthode | URL                    | Rôle                              |
|---------|------------------------|-----------------------------------|
| POST    | `/api/mesures.php`     | Recevoir les données capteurs     |
| GET     | `/api/poubelles.php`   | Liste des poubelles               |
| GET     | `/api/statistiques.php`| Moyennes et heures de pointe      |
| GET     | `/api/alertes.php`     | Liste des alertes actives         |
| GET     | `/api/itineraire.php`  | Itinéraire optimisé               |
| POST    | `/api/login.php`       | Connexion utilisateur             |

### Connexion à MySQL

```php
$pdo = new PDO(
    "mysql:host=localhost;dbname=smart_trash;charset=utf8",
    "root",
    "password",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
```

### Exemple de réponse JSON

```json
{
  "status": "success",
  "data": [
    {"id": 1, "nom": "Poubelle A", "niveau": 45},
    {"id": 2, "nom": "Poubelle B", "niveau": 82}
  ]
}
```

### Livrables

- Fichiers PHP de l'API
- Détection des alertes
- Module de calcul statistiques et itinéraire
- Documentation des endpoints

---

## 6. Partie 3 – Base de données

### Table `poubelles`

| Champ     | Description          |
|-----------|----------------------|
| id        | Identifiant unique   |
| nom       | Nom / code poubelle  |
| latitude  | Coordonnée GPS       |
| longitude | Coordonnée GPS       |
| statut    | Actif / Maintenance  |

### Table `mesures`

| Champ        | Description                  |
|--------------|------------------------------|
| id           | Identifiant                  |
| id_poubelle  | Clé étrangère vers poubelles |
| niveau       | % remplissage                |
| poids        | kg                           |
| temperature  | °C                           |
| date_mesure  | Timestamp                    |

### Table `alertes`

| Champ          | Description        |
|----------------|--------------------|
| id             | Identifiant        |
| id_poubelle    | FK                 |
| type_alerte    | Pleine             |
| statut         | Active / Résolue   |
| date_creation  | Timestamp          |

---

## 7. Partie 4 – Analyse & Optimisation

### Mission

Transformer les données en informations utiles, accessibles via l'API.

### Analyses réalisées

**Moyennes** : Calcul de la moyenne de remplissage par jour, semaine et mois.

```sql
SELECT AVG(niveau) FROM mesures GROUP BY DATE(date_mesure)
```

**Heures de pointe** : Identifier à quelles heures les poubelles se remplissent le plus.

```sql
SELECT HOUR(date_mesure), AVG(niveau) FROM mesures GROUP BY HOUR(date_mesure)
```

**Poubelles les plus utilisées** : Classement par taux moyen de remplissage et nombre d'alertes.

**Itinéraire de collecte** : On sélectionne les poubelles > 70%, on récupère leurs coordonnées GPS, et on calcule un ordre de passage avec l'algorithme du plus proche voisin (Nearest Neighbor) en utilisant la formule de distance Haversine.

### Exemple de résultat

```json
{
  "distance_totale": 12.5,
  "ordre_passage": [
    {"id": 3, "latitude": 36.8, "longitude": 10.1},
    {"id": 7, "latitude": 36.81, "longitude": 10.12}
  ]
}
```

### Scripts Python d'analyse

En plus de l'API PHP, les analyses sont aussi disponibles sous forme de scripts Python dans le dossier `analyse/`. Cela permet de lancer les calculs indépendamment du site web.

**Installation :**

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

## 8. Partie 5 – Site Web (HTML/CSS/JS)

### Mission

Afficher les données en appelant l'API. Le site web est un **client** : il ne touche jamais à la base de données directement.

### Technologies

- HTML / CSS / Bootstrap
- JavaScript (fetch)
- Chart.js pour les graphiques
- Leaflet.js pour les cartes

### Comment ça marche

```javascript
// Exemple : récupérer les statistiques
fetch("/api/statistiques.php")
  .then(response => response.json())
  .then(data => {
    // Afficher les résultats sur la page
  });
```

### Pages du site

- **Dashboard** – Vue globale, nombre de poubelles, alertes en cours
- **Statistiques** – Graphiques des moyennes, heures de pointe
- **Itinéraire** – Carte Leaflet avec l'ordre de passage optimisé
- **Alertes** – Liste des poubelles > 70%
- **Admin** – Ajouter / modifier / supprimer des poubelles
- **Login** – Page de connexion

---

## 9. Sécurité

- Authentification par sessions PHP
- Hashage des mots de passe avec `password_hash()`
- Requêtes préparées PDO (protection contre les injections SQL)
- Validation des données reçues par l'API
- Accès à MySQL uniquement via l'API

---

## 10. Structure du projet

```
smart-trash/
│
├── arduino/                  ← Code Arduino et schémas capteurs
│
├── database/
│   └── schema.sql            ← Script de création des tables
│
├── api/                      ← API PHP (backend)
│   ├── config/
│   │   └── database.php      ← Connexion PDO
│   ├── mesures.php           ← POST : recevoir données capteurs
│   ├── poubelles.php         ← GET : liste des poubelles
│   ├── statistiques.php      ← GET : moyennes, heures de pointe
│   ├── itineraire.php        ← GET : itinéraire optimisé
│   ├── alertes.php           ← GET : alertes actives
│   ├── login.php             ← POST : connexion
│   └── fonctions.php         ← Fonctions utilitaires (Haversine, etc.)
│
├── analyse/                  ← Scripts Python d'analyse
│   ├── config_db.py          ← Connexion à MariaDB
│   ├── moyennes.py           ← Moyennes par jour/semaine/mois
│   ├── heures_pointe.py      ← Détection des heures de pointe
│   ├── classement.py         ← Classement des poubelles
│   ├── itineraire.py         ← Itinéraire optimisé (Haversine + Nearest Neighbor)
│   └── main.py               ← Lancer toutes les analyses
│
├── web/                      ← Site web (front-end)
│   ├── index.html
│   ├── dashboard.html
│   ├── statistiques.html
│   ├── itineraire.html
│   ├── alertes.html
│   ├── admin.html
│   ├── login.html
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── dashboard.js      ← fetch() vers l'API
│   │   ├── statistiques.js
│   │   ├── itineraire.js
│   │   ├── alertes.js
│   │   └── admin.js
│   └── assets/
│
├── docker-compose.yml        ← Lancement de MariaDB + Apache/PHP
└── README.md
```

---

## 11. Lancement du projet avec Docker

### Prérequis

- Docker et Docker Compose installés sur le Raspberry Pi (ou sur votre PC pour tester)
- Pas besoin d'installer PHP, Apache ou MariaDB sur la machine, tout est dans Docker

### Étapes

**1. Cloner ou copier le projet**

```bash
cd ~
# Si vous avez le zip :
unzip smart-trash.zip
cd smart-trash
```

**2. Lancer le projet (une seule commande)**

```bash
docker-compose up -d
```

Cette commande va :
- Télécharger les images MariaDB 10.6 et PHP 8.2 + Apache
- Créer le conteneur `smart_trash_db` (base de données)
- Créer le conteneur `smart_trash_web` (serveur web + API)
- Créer la base `smart_trash` automatiquement
- Exécuter le fichier `database/schema.sql` (création des tables + données de test)
- Installer l'extension PDO MySQL dans le conteneur web
- Stocker les données dans le dossier `db_data/` pour ne rien perdre

**3. Accéder au site**

Le site est accessible à l'adresse : `http://localhost:8080/web/login.html`
L'API est accessible à : `http://localhost:8080/api/poubelles.php`

Connexion : `admin@smarttrash.fr` / `admin123`

**4. Vérifier que tout fonctionne**

```bash
# Voir si les conteneurs tournent
docker ps

# Se connecter à MariaDB pour vérifier
docker exec -it smart_trash_db mysql -u root -ppassword smart_trash

# Dans MariaDB, tester :
SHOW TABLES;
SELECT * FROM poubelles;
EXIT;
```

**5. Arrêter le projet**

```bash
docker-compose down
```

### Commandes utiles

| Commande | Description |
|----------|-------------|
| `docker-compose up -d` | Démarrer tout le projet |
| `docker-compose down` | Arrêter tout le projet |
| `docker-compose logs` | Voir les logs des conteneurs |
| `docker-compose logs web` | Voir les logs du serveur web |
| `docker ps` | Vérifier que les conteneurs tournent |
| `docker exec -it smart_trash_db mysql -u root -ppassword smart_trash` | Se connecter à MariaDB |

### Réinitialiser la base de données

Si vous voulez repartir de zéro (supprimer toutes les données et recréer les tables) :

```bash
docker-compose down
rm -rf db_data
docker-compose up -d
```

---

## 12. Tests

- Envoyer des données JSON simulées à l'API
- Vérifier l'insertion dans MySQL
- Vérifier la détection des alertes > 70%
- Tester le calcul de l'itinéraire
- Tester l'affichage des graphiques et de la carte
- Tester la connexion / déconnexion
- Test complet sur Raspberry Pi

---

## 13. Planning

| Semaine | Phase | Tâches | Livrables |
|---------|-------|--------|-----------|
| 1–2 | Préparation | Cahier des charges, architecture, schéma base | Documents validés |
| 3–4 | API + Base de données | Création de la base, développement de l'API PHP | API fonctionnelle, base opérationnelle |
| 5–6 | Site Web | Pages HTML, appels fetch vers l'API, graphiques, cartes | Site web complet |
| 7 | Analyse | Statistiques, heures de pointe, itinéraire | Endpoints analyse fonctionnels |
| 8 | Intégration | Affichage des analyses sur le site | Pages stats et itinéraire OK |
| 9 | Hardware | Capteurs, Arduino, LoRa, tests | Prototype fonctionnel |
| 10 | Tests & Documentation | Tests complets, corrections, documentation | Projet prêt pour la soutenance |

---

## 14. Améliorations futures

- Machine Learning pour prédire le remplissage
- Notifications SMS / Email
- Application mobile (qui utiliserait la même API)
- Algorithme de tournée plus avancé
- Détection d'anomalies (incendie, surcharge)

---

## 15. Résumé technique

| Couche          | Technologie            | Rôle                          |
|-----------------|------------------------|-------------------------------|
| Hardware        | Arduino + capteurs     | Collecte des données          |
| Communication   | LoRa                   | Transmission au serveur       |
| Backend         | PHP 8 + PDO (API)      | Stockage, analyse, réponses   |
| Base de données | MariaDB                | Historique mesures et alertes |
| Analyse         | PHP + SQL + Python     | Statistiques, itinéraire      |
| Front-end       | HTML + CSS + JS        | Affichage (client de l'API)   |

---

## Conclusion

Smart Trash est un projet IoT complet pour un BTS CIEL, intégrant :

- Des capteurs connectés (Arduino + LoRa)
- Une API PHP comme point d'accès unique à la base
- Une base MySQL
- De l'analyse de données et de l'optimisation d'itinéraire
- Un site web qui consomme l'API