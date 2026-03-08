// ============================================
// Smart Trash - Authentification (partagé)
// ============================================

// Vérifier si l'utilisateur est connecté
function verifierConnexion() {
    var user = localStorage.getItem("user");
    if (!user) {
        window.location.href = "login.html";
        return null;
    }
    return JSON.parse(user);
}

// Afficher le nom de l'utilisateur dans la navbar
function afficherUtilisateur() {
    var user = verifierConnexion();
    if (user) {
        var info = document.getElementById("userInfo");
        if (info) {
            info.textContent = user.nom + " (" + user.role + ")";
        }
    }
}

// Déconnexion
function deconnexion() {
    localStorage.removeItem("user");
    window.location.href = "login.html";
}

// Lancer la vérification au chargement de chaque page
afficherUtilisateur();
