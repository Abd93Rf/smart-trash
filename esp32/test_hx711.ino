#include "HX711.h"

const int LOADCELL_DOUT_PIN = 23;
const int LOADCELL_SCK_PIN = 22;
const int LED_VERTE = 4;
const int LED_JAUNE = 0;
const int LED_ROUGE = 15;

HX711 echelle;

void setup() {
  pinMode(LED_VERTE, OUTPUT);
  pinMode(LED_JAUNE, OUTPUT);
  pinMode(LED_ROUGE, OUTPUT);

  echelle.begin(LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);
  echelle.set_scale();
  echelle.tare();
}

void loop() {
  float poids = echelle.get_units(5);

  if (poids < 1.0) {
    digitalWrite(LED_VERTE, HIGH);
    digitalWrite(LED_JAUNE, LOW);
    digitalWrite(LED_ROUGE, LOW);
  }
  else if (poids >= 1.0 && poids < 10.0) {
    digitalWrite(LED_VERTE, LOW);
    digitalWrite(LED_JAUNE, HIGH);
    digitalWrite(LED_ROUGE, LOW);
  }
  else {
    digitalWrite(LED_VERTE, LOW);
    digitalWrite(LED_JAUNE, LOW);
    digitalWrite(LED_ROUGE, HIGH);
  }

  delay(500);
}