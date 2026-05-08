// ============================================
// Smart Trash — Page Admin (CRUD)
// Gestion des poubelles via fetch()
// console.time() mesure le temps de réponse
// Objectif : < 500 ms par appel
// ============================================

var modeEdition = "ajout";  // "ajout" ou "modification"
var poubelleId  = null;

document.addEventListener("DOMContentLoaded", function () {
    chargerPoubelles();
});

// ============================================
// Charger la liste des poubelles
// ============================================
async function chargerPoubelles() {
    console.time("fetch-admin-liste");
    try {
        var response = await fetch("/api/poubelles.php");
        var resultat = await response.json();
        console.timeEnd("fetch-admin-liste");

        if (resultat.status === "success") {
            var tbody = document.getElementById("tableAdmin");
            if (!tbody) return;
            tbody.innerHTML = "";

            resultat.data.forEach(function (p) {
                var tr = document.createElement("tr");
                tr.innerHTML =
                    "<td>" + p.id + "</td>" +
                    "<td>" + (p.nom || "\u2014") + "</td>" +
                    "<td>" + (p.adresse || "\u2014") + "</td>" +
                    "<td>" + (p.latitude || "\u2014") + "</td>" +
                    "<td>" + (p.longitude || "\u2014") + "</td>" +
                    "<td>" + badgeStatut(p.statut) + "</td>" +
                    "<td>" +
                    '<button class="btn btn-sm btn-outline-primary me-1" ' +
                    'onclick="ouvrirModification(' + p.id + ')">' +
                    '<i class="bi bi-pencil"></i></button>' +
                    '<button class="btn btn-sm btn-outline-danger" ' +
                    'onclick="supprimerPoubelle(' + p.id + ')">' +
                    '<i class="bi bi-trash"></i></button>' +
                    "</td>";
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        console.timeEnd("fetch-admin-liste");
        console.error("Erreur chargerPoubelles :", err);
    }
}

// ============================================
// Ouvrir le modal en mode ajout
// ============================================
function ouvrirAjout() {
    modeEdition = "ajout";
    poubelleId  = null;
    document.getElementById("formPoubelle").reset();
    document.getElementById("modalTitre").textContent = "Ajouter une poubelle";
    var modal = new bootstrap.Modal(document.getElementById("modalPoubelle"));
    modal.show();
}

// ============================================
// Ouvrir le modal en mode modification
// ============================================
async function ouvrirModification(id) {
    console.time("fetch-admin-detail");
    try {
        var response = await fetch("/api/poubelles.php?id=" + id);
        var resultat = await response.json();
        console.timeEnd("fetch-admin-detail");

        if (resultat.status === "success") {
            var p = resultat.data;
            modeEdition = "modification";
            poubelleId  = id;

            document.getElementById("champNom").value       = p.nom       || "";
            document.getElementById("champAdresse").value   = p.adresse   || "";
            document.getElementById("champLatitude").value  = p.latitude  || "";
            document.getElementById("champLongitude").value = p.longitude || "";
            document.getElementById("champStatut").value    = p.statut    || "actif";
            document.getElementById("modalTitre").textContent = "Modifier une poubelle";

            var modal = new bootstrap.Modal(document.getElementById("modalPoubelle"));
            modal.show();
        }
    } catch (err) {
        console.timeEnd("fetch-admin-detail");
        console.error("Erreur ouvrirModification :", err);
    }
}

// ============================================
// Sauvegarder (ajout ou modification)
// ============================================
async function sauvegarderPoubelle() {
    var donnees = {
        nom:       document.getElementById("champNom").value.trim(),
        adresse:   document.getElementById("champAdresse").value.trim(),
        latitude:  parseFloat(document.getElementById("champLatitude").value),
        longitude: parseFloat(document.getElementById("champLongitude").value),
        statut:    document.getElementById("champStatut").value
    };

    if (!donnees.nom) {
        alert("Le nom de la poubelle est obligatoire.");
        return;
    }

    var methode = modeEdition === "ajout" ? "POST" : "PUT";
    var url     = modeEdition === "ajout"
        ? "/api/poubelles.php"
        : "/api/poubelles.php?id=" + poubelleId;

    console.time("fetch-admin-sauvegarde");
    try {
        var response = await fetch(url, {
            method:  methode,
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(donnees)
        });
        var resultat = await response.json();
        console.timeEnd("fetch-admin-sauvegarde");

        if (resultat.status === "success") {
            // Fermer le modal
            var modalEl  = document.getElementById("modalPoubelle");
            var instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) instance.hide();
            // Rafraîchir le tableau
            chargerPoubelles();
        } else {
            alert("Erreur : " + (resultat.message || "Impossible de sauvegarder."));
        }
    } catch (err) {
        console.timeEnd("fetch-admin-sauvegarde");
        console.error("Erreur sauvegarderPoubelle :", err);
    }
}

// ============================================
// Supprimer une poubelle
// ============================================
async function supprimerPoubelle(id) {
    if (!confirm("Confirmer la suppression de cette poubelle ?")) return;

    console.time("fetch-admin-suppression");
    try {
        var response = await fetch("/api/poubelles.php?id=" + id, {
            method: "DELETE"
        });
        var resultat = await response.json();
        console.timeEnd("fetch-admin-suppression");

        if (resultat.status === "success") {
            chargerPoubelles();
        } else {
            alert("Erreur : " + (resultat.message || "Impossible de supprimer."));
        }
    } catch (err) {
        console.timeEnd("fetch-admin-suppression");
        console.error("Erreur supprimerPoubelle :", err);
    }
}

// ============================================
// Badge statut
// ============================================
function badgeStatut(statut) {
    if (!statut) return "\u2014";
    if (statut === "actif")       return '<span class="badge bg-success">Actif</span>';
    if (statut === "maintenance") return '<span class="badge bg-warning text-dark">Maintenance</span>';
    if (statut === "inactif")     return '<span class="badge bg-secondary">Inactif</span>';
    return statut;
}