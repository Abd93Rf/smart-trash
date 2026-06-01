#!/bin/bash
# ============================================
# Génération du certificat auto-signé
# À exécuter UNE SEULE FOIS sur le Raspberry
# ============================================

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout ssl/smart-trash.key \
  -out ssl/smart-trash.crt \
  -subj "/C=FR/ST=Ile-de-France/L=Saint-Denis/O=SmartTrash/CN=smart-trash.local"

echo "Certificat généré dans ssl/"