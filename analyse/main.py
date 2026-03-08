# ============================================
# Smart Trash - Script principal d'analyse
# Lance toutes les analyses en une seule commande
#
# Utilisation : python main.py
# Prérequis : pip install mysql-connector-python
# ============================================

from config_db import connexion
from moyennes import moyennes_par_jour, moyennes_par_semaine, moyennes_par_mois
from heures_pointe import heures_de_pointe
from classement import classement_poubelles
from itineraire import recuperer_poubelles_a_collecter, calculer_itineraire, afficher_itineraire

print("=" * 60)
print("    🗑️  Smart Trash - Analyse des données")
print("=" * 60)

# Connexion à la base de données
db = connexion()

if db:
    # 1. Moyennes
    print("\n" + "=" * 60)
    print("    1. MOYENNES DE REMPLISSAGE")
    print("=" * 60)
    moyennes_par_jour(db)
    moyennes_par_semaine(db)
    moyennes_par_mois(db)

    # 2. Heures de pointe
    print("\n" + "=" * 60)
    print("    2. HEURES DE POINTE")
    print("=" * 60)
    heures_de_pointe(db)

    # 3. Classement
    print("\n" + "=" * 60)
    print("    3. CLASSEMENT DES POUBELLES")
    print("=" * 60)
    classement_poubelles(db)

    # 4. Itinéraire optimisé
    print("\n" + "=" * 60)
    print("    4. ITINÉRAIRE DE COLLECTE")
    print("=" * 60)
    poubelles = recuperer_poubelles_a_collecter(db)
    print(f"\n🔍 {len(poubelles)} poubelle(s) au-dessus de 70%")
    ordre, distance = calculer_itineraire(poubelles)
    afficher_itineraire(ordre, distance)

    # Fermer la connexion
    db.close()

    print("\n" + "=" * 60)
    print("    ✅ Toutes les analyses sont terminées")
    print("=" * 60)
else:
    print("❌ Impossible de lancer les analyses sans connexion à la base.")
