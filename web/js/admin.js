// ============================================
// Smart Trash - Administration
// ============================================

// Mode actuel : "ajout" ou "modification"
var modeEdition = "ajout";

document.addEventListener("DOMContentLoaded", function () {
    chargerPoubelles();
});

// ============================================
// Charger la liste des poubelles
// ============================================
async function chargerPoubelles() {
    try {
        var response = await fetch("/api/poubelles.php");
        var resultat = await response.json();

        if (resultat.status === "success") {
            var tbody = document.getElementById("tableAdmin");
            tbody.innerHTML = "";

            resultat.data.forEach(function (p) {
                var tr = document.createElement("tr");
                tr.innerHTML =
                    "<td>" + p.id + "</td>" +
                    "<td><strong>" + p.nom + "</strong></td>" +
                    "<td>" + (p.adresse || "-") + "</td>" +
                    "<td>" + p.latitude + "</td>" +
                    "<td>" + p.longitude + "</td>" +
                    "<td>" + badgeStatut(p.statut) + "</td>" +
                    "<td>" +
                        '<button class="btn btn-primary btn-sm me-1" onclick="ouvrirModification(' + p.id + ')">' +
                            '<i class="bi bi-pencil"></i>' +
                        "</button>" +
                        '<button class="btn btn-danger btn-sm" onclick="supprimerPoubelle(' + p.id + ', \'' + p.nom + '\')">' +
                            '<i class="bi bi-trash"></i>' +
                        "</button>" +
                    "</td>";
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        console.error("Erreur chargement poubelles :", err);
    }
}

// ============================================
// Ouvrir le modal en mode Ajout
// ============================================
function ouvrirAjout() {
    modeEdition = "ajout";
    document.getElementById("titreModal").textContent = "Ajouter une poubelle";

    // Vider les champs
    document.getElementById("poubelleId").value = "";
    document.getElementById("poubelleNom").value = "";
    document.getElementById("poubelleAdresse").value = "";
    document.getElementById("poubelleLatitude").value = "";
    document.getElementById("poubelleLongitude").value = "";
    document.getElementById("poubelleStatut").value = "actif";
}

// ============================================
// Ouvrir le modal en mode Modification
// ============================================
async function ouvrirModification(id) {
    modeEdition = "modification";
    document.getElementById("titreModal").textContent = "Modifier la poubelle";

    try {
        var response = await fetch("/api/poubelles.php?id=" + id);
        var resultat = await response.json();

        if (resultat.status === "success") {
            var p = resultat.data;
            document.getElementById("poubelleId").value = p.id;
            document.getElementById("poubelleNom").value = p.nom;
            document.getElementById("poubelleAdresse").value = p.adresse || "";
            document.getElementById("poubelleLatitude").value = p.latitude;
            document.getElementById("poubelleLongitude").value = p.longitude;
            document.getElementById("poubelleStatut").value = p.statut;

            // Ouvrir le modal
            var modal = new bootstrap.Modal(document.getElementById("modalPoubelle"));
            modal.show();
        }
    } catch (err) {
        console.error("Erreur chargement poubelle :", err);
    }
}

// ============================================
// Sauvegarder (Ajouter ou Modifier)
// ============================================
async function sauvegarderPoubelle() {
    // Récupérer les valeurs du formulaire
    var donnees = {
        nom: document.getElementById("poubelleNom").value,
        adresse: document.getElementById("poubelleAdresse").value,
        latitude: parseFloat(document.getElementById("poubelleLatitude").value),
        longitude: parseFloat(document.getElementById("poubelleLongitude").value),
        statut: document.getElementById("poubelleStatut").value
    };

    // Vérifier les champs obligatoires
    if (!donnees.nom || isNaN(donnees.latitude) || isNaN(donnees.longitude)) {
        alert("Veuillez remplir le nom, la latitude et la longitude.");
        return;
    }

    try {
        var url, methode;

        if (modeEdition === "ajout") {
            url = "/api/poubelles.php";
            methode = "POST";
        } else {
            var id = document.getElementById("poubelleId").value;
            url = "/api/poubelles.php?id=" + id;
            methode = "PUT";
        }

        var response = await fetch(url, {
            method: methode,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(donnees)
        });

        var resultat = await response.json();

        if (resultat.status === "success") {
            // Fermer le modal
            var modal = bootstrap.Modal.getInstance(document.getElementById("modalPoubelle"));
            modal.hide();

            // Recharger la liste
            chargerPoubelles();
        } else {
            alert("Erreur : " + resultat.message);
        }
    } catch (err) {
        console.error("Erreur sauvegarde :", err);
        alert("Erreur de connexion au serveur.");
    }
}

// ============================================
// Supprimer une poubelle
// ============================================
async function supprimerPoubelle(id, nom) {
    if (!confirm("Supprimer la poubelle \"" + nom + "\" ? Cette action est irréversible.")) {
        return;
    }

    try {
        var response = await fetch("/api/poubelles.php?id=" + id, {
            method: "DELETE"
        });

        var resultat = await response.json();

        if (resultat.status === "success") {
            chargerPoubelles();
        } else {
            alert("Erreur : " + resultat.message);
        }
    } catch (err) {
        console.error("Erreur suppression :", err);
    }
}

// Badge pour le statut
function badgeStatut(statut) {
    if (statut === "actif") return '<span class="badge bg-success">Actif</span>';
    if (statut === "maintenance") return '<span class="badge bg-warning">Maintenance</span>';
    return '<span class="badge bg-secondary">Inactif</span>';
}
