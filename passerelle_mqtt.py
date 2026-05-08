#!/usr/bin/env python3
# ============================================
# Smart Trash — Passerelle MQTT → API
# Avec reconnexion automatique, validation
# JSON et compteur de messages
# ============================================

import paho.mqtt.client as mqtt
import requests
import json
import time
import logging

# Configuration
BROKER_HOST = "localhost"
BROKER_PORT = 1883
TOPIC = "smart_trash/data"
API_URL = "http://localhost:8080/api/mesures.php"
MAX_JSON_SIZE = 1024  # Taille max du JSON en octets (1 Ko)
RECONNECT_DELAY = 5   # Délai entre les tentatives de reconnexion (secondes)

# Compteur de messages
compteur = {"total": 0, "succes": 0, "erreurs": 0, "rejetes": 0, "debut": time.time()}

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%H:%M:%S"
)


def afficher_stats():
    """Affiche les statistiques de la passerelle"""
    duree = time.time() - compteur["debut"]
    if duree > 0:
        msg_par_sec = compteur["total"] / duree
    else:
        msg_par_sec = 0
    logging.info(
        "Stats : %d total | %d succès | %d erreurs | %d rejetés | %.1f msg/s",
        compteur["total"], compteur["succes"], compteur["erreurs"],
        compteur["rejetes"], msg_par_sec
    )


def on_connect(client, userdata, flags, rc):
    """Callback quand la connexion au broker est établie"""
    if rc == 0:
        logging.info("Connecté au broker MQTT (%s:%d)", BROKER_HOST, BROKER_PORT)
        client.subscribe(TOPIC)
        logging.info("Abonné au topic : %s", TOPIC)
    else:
        logging.error("Connexion refusée, code : %d", rc)


def on_disconnect(client, userdata, rc):
    """Callback quand la connexion au broker est perdue"""
    if rc != 0:
        logging.warning("Déconnexion inattendue (code %d), reconnexion...", rc)


def on_message(client, userdata, msg):
    """Callback quand un message arrive sur le topic"""
    compteur["total"] += 1

    # Vérifier la taille du message
    taille = len(msg.payload)
    if taille > MAX_JSON_SIZE:
        compteur["rejetes"] += 1
        logging.warning(
            "Message rejeté : taille %d octets > %d octets max",
            taille, MAX_JSON_SIZE
        )
        return

    # Parser le JSON
    try:
        data = json.loads(msg.payload)
    except json.JSONDecodeError as e:
        compteur["rejetes"] += 1
        logging.warning("JSON invalide rejeté : %s", e)
        return

    # Vérifier les champs obligatoires
    champs = ["id_poubelle", "niveau", "poids", "temperature", "humidite"]
    manquants = [c for c in champs if c not in data]
    if manquants:
        compteur["rejetes"] += 1
        logging.warning("Champs manquants : %s", manquants)
        return

    # Envoyer à l'API
    try:
        response = requests.post(API_URL, json=data, timeout=5)
        if response.status_code == 201 or response.status_code == 200:
            compteur["succes"] += 1
            logging.info("Envoyé : poubelle %s | niveau=%s%%", data["id_poubelle"], data["niveau"])
        else:
            compteur["erreurs"] += 1
            logging.error("API erreur HTTP %d : %s", response.status_code, response.text[:100])
    except requests.exceptions.Timeout:
        compteur["erreurs"] += 1
        logging.error("API timeout (> 5s)")
    except requests.exceptions.ConnectionError:
        compteur["erreurs"] += 1
        logging.error("API inaccessible")

    # Afficher les stats toutes les 10 messages
    if compteur["total"] % 10 == 0:
        afficher_stats()


# ============================================
# Boucle principale avec reconnexion
# ============================================
def main():
    logging.info("=== Passerelle MQTT → API démarrée ===")
    logging.info("Broker : %s:%d | Topic : %s", BROKER_HOST, BROKER_PORT, TOPIC)
    logging.info("API : %s", API_URL)
    logging.info("Taille max JSON : %d octets", MAX_JSON_SIZE)

    client = mqtt.Client()
    client.on_connect = on_connect
    client.on_disconnect = on_disconnect
    client.on_message = on_message

    # Reconnexion automatique intégrée à paho-mqtt
    client.reconnect_delay_set(min_delay=1, max_delay=RECONNECT_DELAY)

    while True:
        try:
            client.connect(BROKER_HOST, BROKER_PORT, keepalive=60)
            client.loop_forever()
        except ConnectionRefusedError:
            logging.error(
                "Broker indisponible, nouvelle tentative dans %ds...",
                RECONNECT_DELAY
            )
            time.sleep(RECONNECT_DELAY)
        except KeyboardInterrupt:
            logging.info("Arrêt demandé par l'utilisateur")
            afficher_stats()
            break


if __name__ == "__main__":
    main()