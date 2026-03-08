# ============================================
# Smart Trash - Optimisation de l'itinéraire
# Algorithme du plus proche voisin (Nearest Neighbor)
# avec calcul de distance Haversine
# ============================================

import math
from config_db import connexion


def distance_haversine(lat1, lon1, lat2, lon2):
    """
    Calculer la distance entre deux points GPS
    en utilisant la formule de Haversine.
    Retourne la distance en kilomètres.
    """
    rayon_terre = 6371  # Rayon de la Terre en km

    # Conversion en radians
    dLat = math.radians(lat2 - lat1)
    dLon = math.radians(lon2 - lon1)

    # Formule de Haversine
    a = (math.sin(dLat / 2) ** 2 +
         math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) *
         math.sin(dLon / 2) ** 2)

    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))

    return rayon_terre * c


def recuperer_poubelles_a_collecter(db):
    """Récupérer les poubelles dont le niveau dépasse 70%"""
    curseur = db.cursor(dictionary=True)

    sql = """
        SELECT p.id, p.nom, p.adresse, p.latitude, p.longitude, m.niveau
        FROM poubelles p
        JOIN mesures m ON m.id = (
            SELECT MAX(id) FROM mesures WHERE id_poubelle = p.id
        )
        WHERE p.statut = 'actif' AND m.niveau > 70
        ORDER BY m.niveau DESC
    """

    curseur.execute(sql)
    return curseur.fetchall()


def calculer_itineraire(poubelles):
    """
    Calculer l'itinéraire optimal avec l'algorithme
    du plus proche voisin (Nearest Neighbor).
    
    Principe :
    1. On part de la poubelle la plus remplie
    2. On va à la poubelle la plus proche
    3. On répète jusqu'à avoir visité toutes les poubelles
    """
    if len(poubelles) == 0:
        return [], 0

    # Copier la liste pour ne pas la modifier
    a_visiter = list(poubelles)
    ordre_passage = []
    distance_totale = 0

    # Commencer par la première (la plus remplie, déjà triée DESC)
    actuelle = a_visiter.pop(0)
    ordre_passage.append(actuelle)

    # Tant qu'il reste des poubelles à visiter
    while len(a_visiter) > 0:
        distance_min = float('inf')
        index_proche = 0

        # Trouver la poubelle la plus proche
        for i, p in enumerate(a_visiter):
            distance = distance_haversine(
                float(actuelle['latitude']), float(actuelle['longitude']),
                float(p['latitude']), float(p['longitude'])
            )

            if distance < distance_min:
                distance_min = distance
                index_proche = i

        # Ajouter la distance parcourue
        distance_totale += distance_min

        # Passer à la poubelle suivante
        actuelle = a_visiter.pop(index_proche)
        ordre_passage.append(actuelle)

    return ordre_passage, round(distance_totale, 2)


def afficher_itineraire(ordre_passage, distance_totale):
    """Afficher l'itinéraire de manière lisible"""

    if len(ordre_passage) == 0:
        print("\n✅ Aucune poubelle à collecter (toutes en dessous de 70%)")
        return

    # Calcul du temps estimé
    # Vitesse moyenne camion en ville : 30 km/h
    # Temps d'arrêt par poubelle : 5 minutes
    vitesse = 30
    temps_arret = 5
    temps_trajet = (distance_totale / vitesse) * 60
    temps_total = round(temps_trajet + len(ordre_passage) * temps_arret)

    print("\n🚛 Itinéraire de collecte optimisé")
    print("=" * 60)
    print(f"📍 Poubelles à collecter : {len(ordre_passage)}")
    print(f"📏 Distance totale : {distance_totale} km")
    print(f"⏱️  Temps estimé : {temps_total} minutes")
    print("=" * 60)

    print("\n📋 Ordre de passage :")
    print("-" * 60)

    for i, p in enumerate(ordre_passage):
        adresse = p['adresse'] if p['adresse'] else "Pas d'adresse"
        niveau = float(p['niveau'])
        print(f"  {i+1}. {p['nom']}")
        print(f"     📍 {adresse}")
        print(f"     📊 Niveau : {niveau:.0f}%")
        print(f"     🌐 GPS : {p['latitude']}, {p['longitude']}")

        if i < len(ordre_passage) - 1:
            # Calculer la distance vers la prochaine
            prochaine = ordre_passage[i + 1]
            dist = distance_haversine(
                float(p['latitude']), float(p['longitude']),
                float(prochaine['latitude']), float(prochaine['longitude'])
            )
            print(f"     ↓ {round(dist, 2)} km vers l'étape suivante")

        print()


# ============================================
# Lancer le script
# ============================================
if __name__ == "__main__":
    db = connexion()
    if db:
        # Récupérer les poubelles à collecter
        poubelles = recuperer_poubelles_a_collecter(db)
        print(f"\n🔍 {len(poubelles)} poubelle(s) au-dessus de 70%")

        # Calculer l'itinéraire
        ordre, distance = calculer_itineraire(poubelles)

        # Afficher le résultat
        afficher_itineraire(ordre, distance)

        db.close()
        print("✅ Optimisation de l'itinéraire terminée")
