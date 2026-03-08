# ============================================
# Smart Trash - Détection des heures de pointe
# Identifier à quelles heures les poubelles
# se remplissent le plus
# ============================================

from config_db import connexion

def heures_de_pointe(db):
    """Trouver les heures où le remplissage moyen est le plus élevé"""
    curseur = db.cursor(dictionary=True)

    sql = """
        SELECT HOUR(date_mesure) AS heure,
               ROUND(AVG(niveau), 1) AS moyenne_niveau,
               COUNT(*) AS nb_mesures
        FROM mesures
        WHERE date_mesure >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY HOUR(date_mesure)
        ORDER BY heure
    """

    curseur.execute(sql)
    resultats = curseur.fetchall()

    print("\n⏰ Remplissage moyen par heure de la journée")
    print("-" * 45)
    print(f"{'Heure':<10} {'Niveau moyen %':<18} {'Mesures'}")
    print("-" * 45)

    heure_max = None
    niveau_max = 0

    for r in resultats:
        # Afficher une barre visuelle
        barre = "█" * int(r['moyenne_niveau'] / 5)
        print(f"{r['heure']:>2}h       {r['moyenne_niveau']:<18} {r['nb_mesures']:<8} {barre}")

        # Trouver l'heure de pointe
        if r['moyenne_niveau'] > niveau_max:
            niveau_max = r['moyenne_niveau']
            heure_max = r['heure']

    if heure_max is not None:
        print(f"\n🔴 Heure de pointe : {heure_max}h avec un niveau moyen de {niveau_max}%")

    return resultats


# ============================================
# Lancer le script
# ============================================
if __name__ == "__main__":
    db = connexion()
    if db:
        heures_de_pointe(db)
        db.close()
        print("\n✅ Analyse des heures de pointe terminée")
