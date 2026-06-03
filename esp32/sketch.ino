#include <WiFi.h>
#include <PubSubClient.h>
#include <DHT.h>
#include <HX711.h>

const char* ssid = "SmartTrash_Wifi";
const char* password = "poubelle2026";
const char* mqtt_server = "192.168.4.1";

IPAddress local_IP(192, 168, 4, 3);
IPAddress gateway(192, 168, 4, 1);
IPAddress subnet(255, 255, 255, 240); // Masque /28 appliqué

WiFiClient espClient;
PubSubClient client(espClient);

const int trigPin = 26;
const int echoPin = 33;

const int ledPin = 19;
const int ledPin2 = 18;

#define DHTPIN 4
#define DHTTYPE DHT22

DHT dht(DHTPIN, DHTTYPE);

const int LOADCELL_DOUT_PIN = 22;
const int LOADCELL_SCK_PIN = 23;

HX711 scale;

float calibration_factor = 780.0;

long duration = 0;
int distance = 0;
bool distanceValide = false;

float temperature = 0.0;
float humidite = 0.0;

float poids = 0.0;

unsigned long dernierEnvoiMqtt = 0;
const long intervalleEnvoi = 2000;

void processUltrasonic() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);

  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);

  digitalWrite(trigPin, LOW);

  duration = pulseIn(echoPin, HIGH, 10000);

  if (duration == 0) {
    distanceValide = false;
    return;
  }

  int nouvelleDistance = duration * 0.034 / 2;

  if (nouvelleDistance >= 2 && nouvelleDistance <= 400) {
    distance = nouvelleDistance;
    distanceValide = true;
  } else {
    distanceValide = false;
  }

  if (distance < 15) {
    digitalWrite(ledPin, HIGH);
    digitalWrite(ledPin2, LOW);
  } else {
    digitalWrite(ledPin2, HIGH);
    digitalWrite(ledPin, LOW);
  }
}

void processDHT() {
  float h = dht.readHumidity();
  float t = dht.readTemperature();

  if (!isnan(h) && !isnan(t)) {
    humidite = h;
    temperature = t;
  }
}

void processPoids() {
  if (scale.is_ready()) {
    poids = scale.get_units(5);

    if (poids < 0) {
      poids = 0;
    }
  }
}

void reconnect() {
  int tentatives = 0;

  while (!client.connected() && tentatives < 5) {

    Serial.print("Connexion MQTT...");

    if (client.connect("ESP32_Poubelle_1")) {
      Serial.println(" OK");
    } else {
      Serial.print(" Erreur rc=");
      Serial.print(client.state());
      Serial.println(" nouvelle tentative dans 2s");

      delay(2000);
      tentatives++;
    }
  }
}

void verifierWifi() {

  if (WiFi.status() == WL_CONNECTED)
    return;

  Serial.println("WiFi perdu ! Reconnexion...");

  WiFi.reconnect();

  unsigned long debut = millis();

  while (WiFi.status() != WL_CONNECTED &&
         millis() - debut < 10000) {

    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {

    Serial.println("\nImpossible de reconnecter le WiFi.");
    Serial.println("Redémarrage ESP32...");

    ESP.restart();
  }

  Serial.println("\nWiFi reconnecté !");
}

void setup() {

  Serial.begin(115200);

  delay(1000);

  Serial.println("===== SMART TRASH =====");

  pinMode(ledPin, OUTPUT);
  pinMode(ledPin2, OUTPUT);

  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);

  dht.begin();

  scale.begin(LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);

  scale.set_scale(calibration_factor);
  //scale.set_scale();
  scale.tare();

  Serial.println("Capteurs initialisés");

  WiFi.mode(WIFI_STA);

  if (!WiFi.config(local_IP, gateway, subnet)) {
    Serial.println("Erreur configuration IP");
  }

  Serial.print("Connexion WiFi : ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  unsigned long debut = millis();

  while (WiFi.status() != WL_CONNECTED &&
         millis() - debut < 15000) {

    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {

    Serial.println("\nConnexion impossible");
    ESP.restart();
  }

  Serial.println("\nWiFi connecté");

  Serial.print("IP : ");
  Serial.println(WiFi.localIP());

  client.setServer(mqtt_server, 1883);
  client.setKeepAlive(60);
}

void loop() {

  verifierWifi();

  if (!client.connected()) {
    reconnect();
  }

  client.loop();

  processUltrasonic();
  processDHT();
  processPoids();

  if (millis() - dernierEnvoiMqtt >= intervalleEnvoi) {

    dernierEnvoiMqtt = millis();

    Serial.println("\n===== MESURES =====");

    // Calcul du niveau déplacé ici pour l'affichage série
    int niveau = 0;

    if (distanceValide) {
      niveau = map(distance, 50, 0, 0, 100);
      niveau = constrain(niveau, 0, 100);

      Serial.print("Niveau de remplissage : ");
      Serial.print(niveau);
      Serial.println(" %");
    } else {
      Serial.println("Niveau de remplissage : Invalide");
    }

    Serial.print("Température : ");
    Serial.print(temperature, 1);
    Serial.println(" °C");

    Serial.print("Humidité : ");
    Serial.print(humidite, 1);
    Serial.println(" %");

    Serial.print("Poids : ");
    Serial.print(poids, 2);
    Serial.println(" kg");

    if (distanceValide) {

      // Construction du JSON mise à jour
      String data = "{";
      data += "\"id_poubelle\":1,";
      data += "\"niveau\":" + String(niveau) + ",";
      data += "\"temperature\":" + String(temperature, 1) + ",";
      data += "\"humidite\":" + String(humidite, 1) + ",";
      data += "\"poids\":" + String(poids, 2);
      data += "}";

      bool resultat = client.publish("smart_trash/data", data.c_str());

      if (resultat) {
        Serial.println("MQTT OK");
      } else {
        Serial.println("MQTT ERREUR");
      }

      Serial.println(data);
    }
    else {
      Serial.println("Mesure ultrason invalide - envoi annulé");
    }
  }

  delay(50);
}