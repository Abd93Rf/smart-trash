#!/usr/bin/env python3
# ============================================
# Smart Trash — Test de charge API
# Envoie 100 requêtes POST en parallèle
# et mesure les performances
# ============================================

import requests
import json
import time
import random
from concurrent.futures import ThreadPoolExecutor, as_completed

API_URL = "http://localhost:8080/api/mesures.php"
NB_REQUETES = 100
NB_THREADS = 10  # 10 threads en parallèle

resultats = []


def envoyer_mesure(numero):
    """Envoie une mesure simulée et mesure le temps de réponse"""
    data = {
        "id_poubelle": random.randint(1, 5),
        "niveau": round(random.uniform(10, 95), 1),
        "poids": round(random.uniform(1, 20), 1),
        "temperature": round(random.uniform(15, 45), 1),
        "humidite": round(random.uniform(30, 90), 1)
    }

    debut = time.time()
    try:
        response = requests.post(API_URL, json=data, timeout=10)
        duree = (time.time() - debut) * 1000  # en ms

        return {
            "numero": numero,
            "status": response.status_code,
            "duree_ms": round(duree, 1),
            "succes": response.status_code in [200, 201]
        }
    except requests.exceptions.Timeout:
        return {"numero": numero, "status": 0, "duree_ms": 10000, "succes": False}
    except requests.exceptions.ConnectionError:
        return {"numero": numero, "status": 0, "duree_ms": 0, "succes": False}


def main():
    print("=" * 50)
    print("TEST DE CHARGE — Smart Trash API")
    print(f"URL : {API_URL}")
    print(f"Requêtes : {NB_REQUETES}")
    print(f"Threads : {NB_THREADS}")
    print("=" * 50)

    # Lancer les requêtes en parallèle
    debut_total = time.time()

    with ThreadPoolExecutor(max_workers=NB_THREADS) as executor:
        futures = {
            executor.submit(envoyer_mesure, i): i
            for i in range(1, NB_REQUETES + 1)
        }

        for future in as_completed(futures):
            resultat = future.result()
            resultats.append(resultat)

            # Afficher la progression
            if len(resultats) % 10 == 0:
                print(f"  {len(resultats)}/{NB_REQUETES} requêtes envoyées...")

    duree_totale = time.time() - debut_total

    # Calculer les statistiques
    succes = [r for r in resultats if r["succes"]]
    echecs = [r for r in resultats if not r["succes"]]
    durees = [r["duree_ms"] for r in succes]

    print()
    print("=" * 50)
    print("RÉSULTATS")
    print("=" * 50)
    print(f"Total requêtes     : {NB_REQUETES}")
    print(f"Succès             : {len(succes)}")
    print(f"Échecs             : {len(echecs)}")
    print(f"Taux de succès     : {len(succes) / NB_REQUETES * 100:.1f}%")
    print()

    if durees:
        print(f"Temps min          : {min(durees):.1f} ms")
        print(f"Temps max          : {max(durees):.1f} ms")
        print(f"Temps moyen        : {sum(durees) / len(durees):.1f} ms")
        print(f"Temps médian       : {sorted(durees)[len(durees) // 2]:.1f} ms")
        print()

        # Vérifier l'objectif < 500 ms
        sous_500 = len([d for d in durees if d < 500])
        print(f"Sous 500 ms        : {sous_500}/{len(durees)} ({sous_500 / len(durees) * 100:.1f}%)")

    print(f"Durée totale       : {duree_totale:.1f} s")
    print(f"Débit              : {NB_REQUETES / duree_totale:.1f} req/s")
    print("=" * 50)

    # Verdict
    if len(succes) == NB_REQUETES:
        print("VERDICT : SUCCÈS — Toutes les requêtes ont été traitées")
    elif len(succes) > NB_REQUETES * 0.95:
        print("VERDICT : ACCEPTABLE — Plus de 95% de succès")
    else:
        print("VERDICT : ÉCHEC — Trop d'erreurs")


if __name__ == "__main__":
    main()