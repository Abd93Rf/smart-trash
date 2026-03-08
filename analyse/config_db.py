# ============================================
# Smart Trash - Connexion à la base de données
# Configuration pour les scripts Python
# ============================================

import mysql.connector

def connexion():
    """Se connecter à la base de données MariaDB"""
    try:
        db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="password",
            database="smart_trash"
        )
        print("✅ Connexion à la base de données réussie")
        return db
    except mysql.connector.Error as err:
        print(f"❌ Erreur de connexion : {err}")
        return None
