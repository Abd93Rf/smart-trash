# ============================================
# Smart Trash - Calcul des moyennes
# Moyenne de remplissage par jour, semaine, mois
# ============================================

from config_db import connexion

def moyennes_par_jour(db):
    """Calculer la moyenne de remplissage par jour (7 derniers jours)"""
    curseur = db.cursor(dictionary=True)

    sql = """
        SELECT DATE(date_mesure) AS jour,
               ROUND(AVG(niveau), 1) AS moyenne_niveau,
               ROUND(AVG(poids), 1) AS moyenne_poids,
               ROUND(AVG(temperature), 1) AS moyenne_temperature,
               COUNT(*) AS nb_mesures
        FROM mesures
        WHERE date_mesure >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(date_mesure)
        ORDER BY jour
    """

    curseur.execute(sql)
    resultats = curseur.fetchall()

    print("\n📊 Moyennes par jour (7 derniers jours)")
    print("-" * 60)
    print(f"{'Jour':<15} {'Niveau %':<12} {'Poids kg':<12} {'Temp °C':<12} {'Mesures'}")
    print("-" * 60)

    for r in resultats:
        print(f"{str(r['jour']):<15} {r['moyenne_niveau']:<12} {r['moyenne_poids']:<12} {r['moyenne_temperature']:<12} {r['nb_mesures']}")

    return resultats


def moyennes_par_semaine(db):
    """Calculer la moyenne de remplissage par semaine (4 dernières semaines)"""
    curseur = db.cursor(dictionary=True)

    sql = """
        SELECT YEARWEEK(date_mesure) AS semaine,
               ROUND(AVG(niveau), 1) AS moyenne_niveau,
               ROUND(AVG(poids), 1) AS moyenne_poids,
               COUNT(*) AS nb_mesures
        FROM mesures
        WHERE date_mesure >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
        GROUP BY YEARWEEK(date_mesure)
        ORDER BY semaine
    """

    curseur.execute(sql)
    resultats = curseur.fetchall()

    print("\n📊 Moyennes par semaine (4 dernières semaines)")
    print("-" * 50)
    print(f"{'Semaine':<15} {'Niveau %':<12} {'Poids kg':<12} {'Mesures'}")
    print("-" * 50)

    for r in resultats:
        print(f"{r['semaine']:<15} {r['moyenne_niveau']:<12} {r['moyenne_poids']:<12} {r['nb_mesures']}")

    return resultats


def moyennes_par_mois(db):
    """Calculer la moyenne de remplissage par mois"""
    curseur = db.cursor(dictionary=True)

    sql = """
        SELECT DATE_FORMAT(date_mesure, '%Y-%m') AS mois,
               ROUND(AVG(niveau), 1) AS moyenne_niveau,
               ROUND(AVG(poids), 1) AS moyenne_poids,
               COUNT(*) AS nb_mesures
        FROM mesures
        GROUP BY DATE_FORMAT(date_mesure, '%Y-%m')
        ORDER BY mois
    """

    curseur.execute(sql)
    resultats = curseur.fetchall()

    print("\n📊 Moyennes par mois")
    print("-" * 50)
    print(f"{'Mois':<15} {'Niveau %':<12} {'Poids kg':<12} {'Mesures'}")
    print("-" * 50)

    for r in resultats:
        print(f"{r['mois']:<15} {r['moyenne_niveau']:<12} {r['moyenne_poids']:<12} {r['nb_mesures']}")

    return resultats


# ============================================
# Lancer le script
# ============================================
if __name__ == "__main__":
    db = connexion()
    if db:
        moyennes_par_jour(db)
        moyennes_par_semaine(db)
        moyennes_par_mois(db)
        db.close()
        print("\n✅ Analyse des moyennes terminée")
