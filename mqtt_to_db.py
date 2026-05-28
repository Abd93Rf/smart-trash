import paho.mqtt.client as mqtt
import mysql.connector
import json
import time

print("Projet lance...")

db = None
while db is None:
    try:
        db = mysql.connector.connect(
            host="127.0.0.1",
            user="web_user",
            password="poubelle2026",
            database="smart_bins"
        )
        print("Connecte a la base de donnees !")
    except Exception:
        print("En attente de la base de donnees...")
        time.sleep(5)

cursor = db.cursor()

def on_message(client, userdata, msg):
    try:
        data = json.loads(msg.payload.decode())
        print("recu :", data)

        sql = """
        INSERT INTO mesures (id_poubelle, niveau, poids, temperature, batterie)
        VALUES (%s, %s, %s, %s, %s)
        """
        values = (
            data.get("id_poubelle", 1),
            data.get("niveau", 0),
            data.get("poids", 0),
            data.get("temperature", 0),
            data.get("batterie", 100)
        )

        cursor.execute(sql, values)
        db.commit()

        print("Enregistre en DB")

    except Exception as e:
        print("Erreur :", e)

client = mqtt.Client()

mqtt_connected = False
while not mqtt_connected:
    try:
        client.connect("localhost", 1883, 60)
        mqtt_connected = True
        print("Connecte au broker MQTT !")
    except Exception:
        print("En attente du broker MQTT...")
        time.sleep(5)

client.subscribe("smart_trash/data")
client.on_message = on_message

print("En attente des donnees MQTT...")
client.loop_forever()