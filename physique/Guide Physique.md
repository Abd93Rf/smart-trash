# Smart Trash - Guide Partie Physique

**Tout ce qu'il faut faire en présentiel (matériel, câblage, installation)**

---

## Matériel nécessaire

### Pour la poubelle connectée (Étudiant 1)

- 1x Arduino UNO ou Nano
- 1x Capteur ultrason HC-SR04
- 1x Capteur de poids HX711 + cellule de charge
- 1x Capteur température/humidité DHT22
- 1x Module LoRa SX1278 (Ra-02) 433MHz
- 1x Antenne LoRa 433MHz
- 1x Résistance 10kΩ (pour le DHT22)
- 1x Breadboard + fils de connexion
- 1x Batterie ou alimentation USB
- 1x Poubelle (maquette ou vraie)

### Pour le serveur (Étudiant 2)

- 1x Raspberry Pi 3 ou 4
- 1x Carte micro SD (16 Go minimum)
- 1x Alimentation Raspberry Pi
- 1x Câble Ethernet (ou connexion WiFi)
- 1x Module LoRa SX1278 (Ra-02) 433MHz (deuxième module, pour la réception)
- 1x Antenne LoRa 433MHz

---

## ÉTAPE 1 — Câblage des capteurs sur l'Arduino

### 1.1 Capteur ultrason HC-SR04 (niveau de remplissage)

Le capteur se place en haut de la poubelle, orienté vers le bas. Il mesure la distance entre le capteur et les déchets.

```
HC-SR04          Arduino
────────         ────────
VCC       →      5V
GND       →      GND
TRIG      →      Pin D9
ECHO      →      Pin D10
```

Comment calculer le pourcentage :
- Poubelle vide = distance maximale (ex: 40 cm)
- Poubelle pleine = distance minimale (ex: 5 cm)
- Formule : `niveau = 100 - ((distance - 5) / (40 - 5) * 100)`

### 1.2 Capteur de poids HX711 + cellule de charge

La cellule de charge se place sous la poubelle. Elle mesure le poids des déchets.

```
Cellule de charge → HX711 (4 fils : rouge, noir, blanc, vert)

HX711            Arduino
────────         ────────
VCC       →      5V
GND       →      GND
DT        →      Pin D3
SCK       →      Pin D2
```

Câblage cellule de charge → HX711 :
- Rouge → E+
- Noir → E-
- Blanc → A-
- Vert → A+

Important : il faudra calibrer la cellule avec un poids connu (ex: une bouteille d'eau de 1 kg).

### 1.3 Capteur température DHT22

```
DHT22            Arduino
────────         ────────
VCC (pin 1)  →   5V
DATA (pin 2) →   Pin D4
              →   Résistance 10kΩ entre VCC et DATA
NC (pin 3)   →   (rien)
GND (pin 4)  →   GND
```

Ne pas oublier la résistance de 10kΩ entre le pin VCC et DATA, sinon les lectures seront instables.

---

## ÉTAPE 2 — Câblage du module LoRa sur l'Arduino (émetteur)

Le module LoRa fonctionne en 3.3V. Ne jamais le brancher sur le 5V.

```
Module LoRa      Arduino UNO
────────         ────────
VCC       →      3.3V  ⚠️ PAS 5V !
GND       →      GND
SCK       →      Pin D13
MISO      →      Pin D12
MOSI      →      Pin D11
NSS (CS)  →      Pin D10
RST       →      Pin D9
DIO0      →      Pin D2
```

⚠️ Conflit de pins : le HC-SR04 utilise aussi D9 et D10. Il faut donc changer les pins du HC-SR04 :

```
Pins corrigés :
- HC-SR04 TRIG  →  Pin D7
- HC-SR04 ECHO  →  Pin D8
- HX711 DT      →  Pin D3
- HX711 SCK     →  Pin D4
- DHT22 DATA    →  Pin D5
- LoRa NSS      →  Pin D10
- LoRa RST      →  Pin D9
- LoRa DIO0     →  Pin D2
```

Ne pas oublier de brancher l'antenne LoRa sur le module avant de l'allumer, sinon le module peut être endommagé.

---

## ÉTAPE 3 — Programmer l'Arduino

### 3.1 Installer les bibliothèques dans l'IDE Arduino

Aller dans Outils → Gérer les bibliothèques, puis installer :

- `LoRa` par Sandeep Mistry
- `HX711` par Bogdan Necula
- `DHT sensor library` par Adafruit
- `Adafruit Unified Sensor` (dépendance du DHT)

### 3.2 Logique du programme

Le code Arduino doit faire cette boucle :

```
1. Lire le capteur ultrason → calculer le niveau en %
2. Lire le capteur de poids → récupérer le poids en kg
3. Lire le capteur DHT22 → récupérer la température
4. Formater les données en JSON
5. Envoyer le JSON via LoRa
6. Attendre X minutes (mode veille)
7. Recommencer
```

### 3.3 Format du JSON envoyé

```json
{
  "id_poubelle": 1,
  "niveau": 75,
  "poids": 12.4,
  "temperature": 28.5
}
```

### 3.4 Calibration du capteur de poids

Avant d'utiliser le HX711, il faut le calibrer :

1. Lancer un sketch de calibration (fourni avec la bibliothèque HX711)
2. Sans rien sur la cellule, noter la valeur "offset"
3. Poser un poids connu (ex: 1 kg) et noter la valeur
4. Calculer le facteur de calibration
5. Mettre ces valeurs dans le code final

### 3.5 Calibration du capteur ultrason

1. Mesurer la hauteur intérieure de la poubelle (ex: 40 cm)
2. Mettre cette valeur dans le code comme `HAUTEUR_MAX`
3. Distance minimale (poubelle pleine) : environ 5 cm
4. Tester avec la poubelle vide puis pleine

---

## ÉTAPE 4 — Installer le Raspberry Pi

### 4.1 Installer Raspberry Pi OS

1. Télécharger Raspberry Pi Imager depuis raspberrypi.com
2. Flasher Raspberry Pi OS Lite (64-bit) sur la carte SD
3. Avant d'éjecter, activer SSH et configurer le WiFi dans Imager (icône engrenage)
4. Insérer la carte SD dans le Raspberry Pi
5. Brancher l'alimentation et le câble Ethernet

### 4.2 Première connexion

```bash
# Trouver l'adresse IP du Raspberry Pi (depuis un PC sur le même réseau)
ping raspberrypi.local

# Se connecter en SSH
ssh pi@raspberrypi.local
# Mot de passe par défaut : raspberry (à changer)
```

### 4.3 Mettre à jour le système

```bash
sudo apt update
sudo apt upgrade -y
```

### 4.4 Installer les dépendances

```bash
# PHP et extensions
sudo apt install -y php php-mysql php-json

# Docker (pour MariaDB)
curl -sSL https://get.docker.com | sh
sudo usermod -aG docker pi
# Se déconnecter et reconnecter pour que le groupe soit pris en compte

# Docker Compose
sudo apt install -y docker-compose

# Python et pip (pour les scripts d'analyse)
sudo apt install -y python3 python3-pip
pip3 install mysql-connector-python

# SPI (pour le module LoRa)
sudo apt install -y python3-spidev
pip3 install pyLoRa
```

### 4.5 Activer le SPI (pour le LoRa)

```bash
sudo raspi-config
# → Interface Options → SPI → Enable
sudo reboot
```

Vérifier que le SPI est activé :

```bash
ls /dev/spi*
# Doit afficher : /dev/spidev0.0  /dev/spidev0.1
```

---

## ÉTAPE 5 — Câblage du module LoRa sur le Raspberry Pi (récepteur)

Ce deuxième module LoRa reçoit les données envoyées par l'Arduino.

```
Module LoRa      Raspberry Pi (GPIO)
────────         ────────────────────
VCC       →      3.3V         (Pin 1)
GND       →      GND          (Pin 6)
SCK       →      GPIO 11      (Pin 23)  SCLK
MISO      →      GPIO 9       (Pin 21)  MISO
MOSI      →      GPIO 10      (Pin 19)  MOSI
NSS (CS)  →      GPIO 8       (Pin 24)  CE0
RST       →      GPIO 25      (Pin 22)
DIO0      →      GPIO 24      (Pin 18)
```

Rappel : le module LoRa fonctionne en 3.3V, le Raspberry Pi aussi sur ses GPIO, donc pas de problème de tension ici.

Schéma des pins du Raspberry Pi (vue du dessus, connecteur USB en bas) :

```
        3.3V [1]  [2]  5V
   GPIO 2   [3]  [4]  5V
   GPIO 3   [5]  [6]  GND        ← GND du LoRa
   GPIO 4   [7]  [8]  GPIO 14
        GND [9]  [10] GPIO 15
  GPIO 17  [11]  [12] GPIO 18    ← DIO0 du LoRa
  GPIO 27  [13]  [14] GND
  GPIO 22  [15]  [16] GPIO 23
      3.3V [17]  [18] GPIO 24
  GPIO 10  [19]  [20] GND        ← MOSI du LoRa
   GPIO 9  [21]  [22] GPIO 25    ← MISO / RST du LoRa
  GPIO 11  [23]  [24] GPIO 8     ← SCK / NSS du LoRa
       GND [25]  [26] GPIO 7
```

---

## ÉTAPE 6 — Déployer le projet sur le Raspberry Pi

### 6.1 Copier le projet

Depuis votre PC, copier le dossier smart-trash sur le Raspberry Pi :

```bash
# Depuis votre PC
scp -r smart-trash/ pi@raspberrypi.local:~/
```

Ou bien utiliser une clé USB.

### 6.2 Lancer MariaDB avec Docker

```bash
cd ~/smart-trash
docker-compose up -d

# Vérifier que ça tourne
docker ps

# Vérifier la base de données
docker exec -it smart_trash_db mysql -u root -ppassword smart_trash -e "SHOW TABLES;"
```

### 6.3 Lancer le serveur PHP

```bash
cd ~/smart-trash
php -S 0.0.0.0:8080 -t .
```

Le site est maintenant accessible depuis n'importe quel appareil du réseau :

```
http://IP_DU_RASPBERRY:8080/web/login.html
```

Pour trouver l'IP : `hostname -I`

### 6.4 Lancer le script de réception LoRa

Dans un deuxième terminal (ou en screen/tmux) :

```bash
cd ~/smart-trash
python3 reception_lora.py
```

Ce script écoute le LoRa et envoie les données à l'API automatiquement.

---

## ÉTAPE 7 — Tests

### 7.1 Tester les capteurs individuellement

Avant de tout assembler, tester chaque capteur séparément avec un petit sketch Arduino :

1. Tester le HC-SR04 seul → vérifier les distances affichées dans le moniteur série
2. Tester le HX711 seul → calibrer avec un poids connu
3. Tester le DHT22 seul → vérifier température et humidité
4. Tester le LoRa seul → envoyer "Hello" et vérifier la réception sur le Raspberry Pi

### 7.2 Tester la chaîne complète

1. L'Arduino lit les capteurs et envoie le JSON via LoRa
2. Le Raspberry Pi reçoit le JSON via LoRa
3. Le script Python envoie le JSON à l'API (`POST /api/mesures.php`)
4. Vérifier dans la base que la mesure est bien enregistrée
5. Vérifier sur le site web que les données s'affichent

### 7.3 Tester la portée LoRa

1. Éloigner progressivement l'Arduino du Raspberry Pi
2. Noter la distance maximale à laquelle la réception fonctionne encore
3. Tester en intérieur et en extérieur
4. Documenter les résultats (distance, obstacles, taux de perte)

### 7.4 Tester l'autonomie batterie

1. Brancher l'Arduino sur batterie (pas USB)
2. Configurer l'intervalle d'envoi (ex: toutes les 5 minutes)
3. Laisser tourner et noter combien de temps ça tient
4. Documenter le résultat

---

## Résumé des étapes

| # | Étape  | Temps estimé |
|---|-------|-------------|
| 1 | Câbler les 3 capteurs sur l'Arduino  | 1h |
| 2 | Câbler le LoRa émetteur sur l'Arduino  | 30min |
| 3 | Programmer l'Arduino (capteurs + LoRa)  | 2-3h |
| 4 | Installer Raspberry Pi OS + dépendances  | 1h |
| 5 | Câbler le LoRa récepteur sur le Raspberry Pi | 30min |
| 6 | Déployer le projet (Docker + PHP)  | 30min |
| 7 | Écrire/lancer le script de réception LoRa | 1h |
| 8 | Tests individuels des capteurs  | 1h |
| 9 | Test chaîne complète  | 1-2h |
| 10 | Tests portée LoRa + autonomie batterie | 1-2h |

---

## Problèmes fréquents

**Le HC-SR04 affiche 0 ou des valeurs incohérentes**
→ Vérifier le câblage TRIG et ECHO. Vérifier que rien ne bloque le capteur.

**Le HX711 donne des valeurs qui bougent beaucoup**
→ Recalibrer. Vérifier que la cellule de charge est bien fixée et stable.

**Le DHT22 ne répond pas**
→ Vérifier la résistance 10kΩ entre VCC et DATA. Vérifier le pin dans le code.

**Le LoRa n'envoie pas / ne reçoit pas**
→ Vérifier que les deux modules sont sur la même fréquence (433 MHz). Vérifier que l'antenne est branchée. Vérifier le câblage SPI.

**Docker ne démarre pas sur le Raspberry Pi**
→ Vérifier que Docker est bien installé : `docker --version`. Vérifier que l'utilisateur est dans le groupe docker : `groups`.

**Le site web n'est pas accessible depuis un autre PC**
→ Vérifier que PHP écoute sur `0.0.0.0` (pas `localhost`). Vérifier l'IP avec `hostname -I`. Vérifier le pare-feu.