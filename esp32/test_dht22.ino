#include <DHT.h>

// Configuration du capteur
#define DHTPIN 4
#define DHTTYPE DHT22

DHT dht(DHTPIN, DHTTYPE);

void setup() {
  Serial.begin(115200);
  dht.begin();

  Serial.println("=== TEST HUMIDITE ===");
}

void loop() {
  float h = dht.readHumidity();
  float temp = dht.readTemperature();

  if (isnan(h) || isnan(temp)) {
    Serial.println("Erreur capteur !");
    delay(2000);
    return;
  }

  Serial.print("Humidite: ");
  Serial.print(h);
  Serial.println(" %");

  Serial.print("Témperature: ");
  Serial.print(temp);
  Serial.println(" °C");
  }