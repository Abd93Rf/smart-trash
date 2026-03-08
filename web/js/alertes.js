// ============================================
// Smart Trash - Alertes
// ============================================

document.addEventListener("DOMContentLoaded", function () {
    chargerAlertes("active");
});

// ============================================
// Charger les alertes depuis l'API
// ============================================
async function chargerAlertes(statut) {
    try {
        var url = "/api/alertes.php";
        if (statut) {
            url += "?statut=" + statut;
        }

        var response = await fetch(url);
        var resultat = await response.json();

        if (resultat.status === "success") {
            afficherAlertes(resultat.data);
        }
    } catch (err) {
        console.error("Erreur chargement alertes :", err);
        document.getElementById("listeAlertes").innerHTML =
            '<div class="alert alert-danger">Erreur de connexion au serveur.</div>';
    }
}

// ============================================
// Afficher les alertes
// ============================================
function afficherAlertes(alertes) {
    var conteneur = document.getElementById("listeAlertes");
    conteneur.innerHTML = "";

    if (alertes.length === 0) {
        conteneur.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Aucune alerte trouvée.</div>';
        return;
    }

    alertes.forEach(function (a) {
        var estActive = a.statut === "active";
        var classeAlerte = estActive ? "alerte-active" : "alerte-resolue";

        // Icône selon le type d'alerte
        var icone = "bi-trash-fill";
        if (a.type_alerte === "temperature") icone = "bi-thermometer-high";
        if (a.type_alerte === "maintenance") icone = "bi-tools";

        var div = document.createElement("div");
        div.className = "card mb-3 " + classeAlerte;
        div.innerHTML =
            '<div class="card-body d-flex justify-content-between align-items-center">' +
                '<div class="d-flex align-items-center">' +
                    '<i class="bi ' + icone + ' fs-3 me-3 ' + (estActive ? "text-danger" : "text-success") + '"></i>' +
                    '<div>' +
                        '<h6 class="mb-1">' + a.nom_poubelle + '</h6>' +
                        '<p class="mb-1">' + (a.message || a.type_alerte) + '</p>' +
                        '<small class="text-muted">' + formaterDate(a.date_creation) + '</small>' +
                        (a.date_resolution ? '<br><small class="text-success">Résolue le ' + formaterDate(a.date_resolution) + '</small>' : '') +
                    '</div>' +
                '</div>' +
                '<div>' +
                    (estActive
                        ? '<button class="btn btn-success btn-sm" onclick="resoudreAlerte(' + a.id + ')"><i class="bi bi-check-lg"></i> Résoudre</button>'
                        : '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Résolue</span>'
                    ) +
                '</div>' +
            '</div>';

        conteneur.appendChild(div);
    });
}

// ============================================
// Résoudre une alerte
// ============================================
async function resoudreAlerte(id) {
    if (!confirm("Confirmer la résolution de cette alerte ?")) return;

    try {
        var response = await fetch("/api/alertes.php?id=" + id, {
            method: "PUT"
        });

        var resultat = await response.json();

        if (resultat.status === "success") {
            // Recharger la liste
            chargerAlertes("active");
        } else {
            alert("Erreur : " + resultat.message);
        }
    } catch (err) {
        console.error("Erreur résolution alerte :", err);
    }
}

// ============================================
// Filtrer les alertes (boutons)
// ============================================
function filtrerAlertes(statut, bouton) {
    // Mettre à jour les boutons actifs
    var boutons = document.querySelectorAll(".btn-group .btn");
    boutons.forEach(function (b) {
        b.className = b.className.replace(" active", "");
        b.className = b.className.replace("btn-danger", "btn-outline-danger");
        b.className = b.className.replace("btn-success", "btn-outline-success");
        b.className = b.className.replace("btn-secondary", "btn-outline-secondary");
    });

    // Activer le bouton cliqué
    bouton.classList.add("active");
    if (statut === "active") bouton.className = "btn btn-danger active";
    else if (statut === "resolue") bouton.className = "btn btn-success active";
    else bouton.className = "btn btn-secondary active";

    chargerAlertes(statut);
}

// Formater une date
function formaterDate(dateStr) {
    if (!dateStr) return "";
    var d = new Date(dateStr);
    return d.toLocaleDateString("fr-FR") + " à " + d.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });
}
