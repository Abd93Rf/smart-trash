// ============================================
// Smart Trash - Sketch ESP32 complet
// 3 capteurs : HC-SR04 + DHT22 + HX711
// Communication : MQTT via WiFi
// ============================================

#include <WiFi.h>
#include <PubSubClient.h>
#include <DHT.h>
#include "HX711.h"

// ============================================
// Configuration WiFi + MQTT (NE PAS MODIFIER)
// ============================================
const char* ssid = "SmartTrash_Wifi";
const char* password = "poubelle2026";
const char* mqtt_server = "192.168.4.1";

IPAddress local_IP(192, 168, 4, 3);
IPAddress gateway(192, 168, 4, 1);
IPAddress subnet(255, 255, 255, 0);

WiFiClient espClient;
PubSubClient client(espClient);

// ============================================
// Pins HC-SR04 (ultrason) - CALIBRE
// ============================================
int trigPin = 26;
int echoPin = 33;
int ledPin = 19;   // LED verte (poubelle vide)
int ledPin2 = 18;  // LED rouge (poubelle pleine)

long duration;
int distance = 0;

// ============================================
// Pins DHT22 (temperature + humidite)
// ============================================
#define DHTPIN 4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

float temperature = 0.0;
float humidite = 0.0;

// ============================================
// Pins HX711 (poids) - CALIBRE
// ============================================
const int LOADCELL_DOUT_PIN = 23;
const int LOADCELL_SCK_PIN = 22;
HX711 echelle;
float poids = 0.0;

// ============================================
// Intervalle d'envoi MQTT
// ============================================
unsigned long dernierEnvoiMqtt = 0;
const long intervalleEnvoi = 2000;

// ============================================
// Lecture ultrason (NE PAS MODIFIER - CALIBRE)
// ============================================
void processUltrasonic() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);

  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  duration = pulseIn(echoPin, HIGH, 30000);

  if (duration == 0) {
    return;
  }

  int nouvelleDistance = duration * 0.034 / 2;

  if (nouvelleDistance >= 2 && nouvelleDistance <= 400) {
    distance = nouvelleDistance;
  }

  if (distance < 50) {
    // Poubelle pleine
    digitalWrite(ledPin, HIGH);
    digitalWrite(ledPin2, LOW);
  } else {
    // Poubelle vide
    digitalWrite(ledPin2, HIGH);
    digitalWrite(ledPin, LOW);
  }
}

// ============================================
// Lecture DHT22 (temperature + humidite)
// ============================================
void processDHT() {
  float h = dht.readHumidity();
  float t = dht.readTemperature();

  // Verifier que les valeurs sont valides
  if (!isnan(h) && !isnan(t)) {
    humidite = h;
    temperature = t;
  }
}

// ============================================
// Lecture HX711 (poids)
// ============================================
void processWeight() {
  if (echelle.is_ready()) {
    float lecture = echelle.get_units(5);
    // Ignorer les valeurs negatives (bruit du capteur)
    if (lecture >= 0) {
      poids = lecture;
    }
  }
}

// ============================================
// Reconnexion MQTT (NE PAS MODIFIER)
// ============================================
void reconnect() {
  while (!client.connected()) {
    Serial.print("Connexion MQTT...");
    if (client.connect("ESP32_Poubelle_1")) {
      Serial.println("OK");
    } else {
      Serial.print("Erreur, rc=");
      Serial.print(client.state());
      Serial.println(" retry...");
      delay(2000);
    }
  }
}

// ============================================
// Setup
// ============================================
void setup() {
  Serial.begin(115200);

  // Pins ultrason
  pinMode(ledPin, OUTPUT);
  pinMode(ledPin2, OUTPUT);
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);

  // Init DHT22
  dht.begin();

  // Init HX711
  echelle.begin(LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);
  echelle.set_scale();  // Calibration deja faite
  echelle.tare();

  // Connexion WiFi (NE PAS MODIFIER)
  WiFi.mode(WIFI_STA);

  if (!WiFi.config(local_IP, gateway, subnet)) {
    Serial.println("Erreur IP statique");
  }

  Serial.print("Connexion WiFi...");
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi OK !");
  Serial.print("IP ESP32 : ");
  Serial.println(WiFi.localIP());

  // Connexion MQTT
  client.setServer(mqtt_server, 1883);
  client.setKeepAlive(60);
}

// ============================================
// Loop principal
// ============================================
void loop() {
  // Reconnexion MQTT si deconnecte
  if (!client.connected()) {
    reconnect();
  }

  client.loop();

  // Lecture des 3 capteurs
  processUltrasonic();
  processDHT();
  processWeight();

  // Affichage console
  Serial.print("Distance: ");
  Serial.print(distance);
  Serial.print(" cm | Poids: ");
  Serial.print(poids, 1);
  Serial.print(" kg | Temp: ");
  Serial.print(temperature, 1);
  Serial.print(" C | Humid: ");
  Serial.print(humidite, 1);
  Serial.println(" %");

  // Envoi MQTT a intervalle regulier
  if (millis() - dernierEnvoiMqtt >= intervalleEnvoi) {
    dernierEnvoiMqtt = millis();

    // Calcul du niveau (0-100%) a partir de la distance
    int niveau = map(distance, 50, 0, 0, 100);
    niveau = constrain(niveau, 0, 100);

    // Construction du JSON avec les vraies valeurs
    String data = "{";
    data += "\"id_poubelle\":1,";
    data += "\"distance\":" + String(distance) + ",";
    data += "\"niveau\":" + String(niveau) + ",";
    data += "\"poids\":" + String(poids, 1) + ",";
    data += "\"temperature\":" + String(temperature, 1) + ",";
    data += "\"humidite\":" + String(humidite, 1);
    data += "}";

    // Envoi au broker MQTT
    client.publish("smart_trash/data", data.c_str());

    Serial.println("Donnees envoyees :");
    Serial.println(data);
  }

  delay(50);
}