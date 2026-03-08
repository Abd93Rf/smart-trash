# ============================================
# Smart Trash - Classement des poubelles
# Identifier les poubelles les plus utilisées
# ============================================

from config_db import connexion

def classement_poubelles(db):
    """Classer les poubelles par taux moyen de remplissage"""
    curseur = db.cursor(dictionary=True)

    sql = """
        SELECT p.id, p.nom, p.adresse,
               ROUND(AVG(m.niveau), 1) AS moyenne_niveau,
               ROUND(MAX(m.niveau), 1) AS niveau_max,
               COUNT(DISTINCT m.id) AS nb_mesures,
               COUNT(DISTINCT a.id) AS nb_alertes
        FROM poubelles p
        LEFT JOIN mesures m ON p.id = m.id_poubelle
        LEFT JOIN alertes a ON p.id = a.id_poubelle
        GROUP BY p.id, p.nom, p.adresse
        ORDER BY moyenne_niveau DESC
    """

    curseur.execute(sql)
    resultats = curseur.fetchall()

    print("\n🏆 Classement des poubelles les plus utilisées")
    print("-" * 75)
    print(f"{'#':<4} {'Nom':<15} {'Adresse':<25} {'Moy %':<8} {'Max %':<8} {'Alertes'}")
    print("-" * 75)

    for i, r in enumerate(resultats):
        adresse = r['adresse'] if r['adresse'] else "-"
        # Limiter la longueur de l'adresse pour l'affichage
        if len(adresse) > 23:
            adresse = adresse[:20] + "..."

        print(f"{i+1:<4} {r['nom']:<15} {adresse:<25} {r['moyenne_niveau']:<8} {r['niveau_max']:<8} {r['nb_alertes']}")

    # Résumé
    if resultats:
        plus_utilisee = resultats[0]
        print(f"\n🔴 Poubelle la plus sollicitée : {plus_utilisee['nom']} (moyenne {plus_utilisee['moyenne_niveau']}%)")

    return resultats


# ============================================
# Lancer le script
# ============================================
if __name__ == "__main__":
    db = connexion()
    if db:
        classement_poubelles(db)
        db.close()
        print("\n✅ Classement terminé")
