// ============================================
// Smart Trash - Dashboard
// ============================================

document.addEventListener("DOMContentLoaded", function () {
    chargerResume();
    chargerPoubelles();
});

// ============================================
// Charger le résumé global (cartes du haut)
// ============================================
async function chargerResume() {
    try {
        var response = await fetch("/api/statistiques.php?type=global");
        var resultat = await response.json();

        if (resultat.status === "success") {
            var data = resultat.data;

            document.getElementById("totalPoubelles").textContent = data.total_poubelles;
            document.getElementById("alertesActives").textContent = data.alertes_actives;
            document.getElementById("poubellesACollecter").textContent = data.poubelles_a_collecter;

            var barre = document.getElementById("barreNiveauMoyen");
            barre.style.width = data.niveau_moyen + "%";
            barre.textContent = data.niveau_moyen + "%";
            barre.className = "progress-bar " + couleurNiveau(data.niveau_moyen);
        }
    } catch (err) {
        console.error("Erreur chargement résumé :", err);
    }
}

// ============================================
// Charger la liste des poubelles
// ============================================
async function chargerPoubelles() {
    try {
        var response = await fetch("/api/poubelles.php");
        var resultat = await response.json();

        if (resultat.status === "success") {
            var tbody = document.getElementById("tablePoubelles");
            tbody.innerHTML = "";

            resultat.data.forEach(function (p) {
                var niveau = p.dernier_niveau ? parseFloat(p.dernier_niveau) : 0;
                var poids = p.dernier_poids ? parseFloat(p.dernier_poids) : 0;
                var temp = p.derniere_temperature ? parseFloat(p.derniere_temperature) : 0;
                var humidite = p.derniere_humidite ? parseFloat(p.derniere_humidite) : 0;

                var tr = document.createElement("tr");
                tr.innerHTML =
                    "<td><strong>" + p.nom + "</strong></td>" +
                    "<td>" + (p.adresse || "-") + "</td>" +
                    "<td>" +
                    '<div class="progress progress-niveau" style="min-width:100px">' +
                    '<div class="progress-bar ' + couleurNiveau(niveau) + '" style="width:' + niveau + '%">' +
                    Math.round(niveau) + "%" +
                    "</div>" +
                    "</div>" +
                    "</td>" +
                    "<td>" + badgePoids(poids) + "</td>" +
                    "<td>" + badgeTemperature(temp) + "</td>" +
                    "<td>" + badgeHumidite(humidite) + "</td>" +
                    "<td>" + badgeStatut(p.statut) + "</td>" +
                    "<td>" + formaterDate(p.derniere_mesure) + "</td>";

                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        console.error("Erreur chargement poubelles :", err);
    }
}

// ============================================
// Fonctions utilitaires d'affichage
// ============================================

// Couleur de la barre selon le niveau
function couleurNiveau(niveau) {
    if (niveau > 90) return "bg-danger";
    if (niveau > 70) return "bg-warning";
    return "bg-success";
}

// Badge pour le poids (avec alerte si > 15 kg)
function badgePoids(poids) {
    if (poids > 15) return '<span class="badge bg-danger">' + poids.toFixed(1) + ' kg</span>';
    if (poids > 10) return '<span class="badge bg-warning text-dark">' + poids.toFixed(1) + ' kg</span>';
    return poids.toFixed(1) + " kg";
}

// Badge pour la température (avec alerte si > 40°C)
function badgeTemperature(temp) {
    if (temp > 40) return '<span class="badge bg-danger">' + temp.toFixed(1) + ' °C</span>';
    if (temp > 30) return '<span class="badge bg-warning text-dark">' + temp.toFixed(1) + ' °C</span>';
    return temp.toFixed(1) + " °C";
}

// Badge pour l'humidité (avec alerte si > 80%)
function badgeHumidite(humidite) {
    if (humidite > 80) return '<span class="badge bg-danger">' + humidite.toFixed(1) + ' %</span>';
    if (humidite > 60) return '<span class="badge bg-warning text-dark">' + humidite.toFixed(1) + ' %</span>';
    if (humidite === 0) return "-";
    return humidite.toFixed(1) + " %";
}

// Badge pour le statut
function badgeStatut(statut) {
    if (statut === "actif") return '<span class="badge bg-success">Actif</span>';
    if (statut === "maintenance") return '<span class="badge bg-warning">Maintenance</span>';
    return '<span class="badge bg-secondary">Inactif</span>';
}

// Formater une date MySQL pour l'affichage
function formaterDate(dateStr) {
    if (!dateStr) return "-";
    var d = new Date(dateStr);
    return d.toLocaleDateString("fr-FR") + " " + d.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });
}