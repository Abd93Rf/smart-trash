// ============================================
// Smart Trash — Dashboard
// Chargement des données via fetch()
// console.time() mesure le temps de réponse
// Objectif : < 500 ms par appel
// ============================================

document.addEventListener("DOMContentLoaded", function () {
    chargerResume();
    chargerPoubelles();
});

// ============================================
// Chargement des cartes de résumé
// ============================================
async function chargerResume() {
    console.time("fetch-resume");
    try {
        var response = await fetch("/api/statistiques.php?type=global");
        var resultat = await response.json();
        console.timeEnd("fetch-resume");

        if (resultat.status === "success") {
            var d = resultat.data;
            document.getElementById("totalPoubelles").textContent = d.total_poubelles   || 0;
            document.getElementById("alertesActives").textContent = d.alertes_actives   || 0;
            document.getElementById("aCollecter").textContent     = d.a_collecter       || 0;

            // Barre de progression niveau moyen
            var niveau = d.niveau_moyen || 0;
            var barre  = document.getElementById("barreNiveau");
            var label  = document.getElementById("labelNiveau");
            if (barre) {
                barre.style.width = niveau + "%";
                barre.className   = "progress-bar " + classCouleurNiveau(niveau);
                barre.setAttribute("aria-valuenow", niveau);
            }
            if (label) {
                label.textContent = "Niveau moyen : " + niveau.toFixed(1) + "%";
            }
        }
    } catch (err) {
        console.timeEnd("fetch-resume");
        console.error("Erreur chargerResume :", err);
    }
}

// ============================================
// Chargement du tableau des poubelles
// ============================================
async function chargerPoubelles() {
    console.time("fetch-poubelles");
    try {
        var response = await fetch("/api/poubelles.php");
        var resultat = await response.json();
        console.timeEnd("fetch-poubelles");

        if (resultat.status === "success") {
            var tbody = document.getElementById("tablePoubelles");
            if (!tbody) return;
            tbody.innerHTML = "";

            resultat.data.forEach(function (p) {
                var tr = document.createElement("tr");
                tr.innerHTML =
                    "<td>" + (p.nom || "\u2014") + "</td>" +
                    "<td>" + (p.adresse || "\u2014") + "</td>" +
                    "<td>" + barreNiveau(p.dernier_niveau) + "</td>" +
                    "<td>" + badgePoids(p.dernier_poids) + "</td>" +
                    "<td>" + badgeTemperature(p.derniere_temperature) + "</td>" +
                    "<td>" + badgeHumidite(p.derniere_humidite) + "</td>" +
                    "<td>" + badgeStatut(p.statut) + "</td>" +
                    "<td>" + formatDate(p.derniere_mesure) + "</td>";
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        console.timeEnd("fetch-poubelles");
        console.error("Erreur chargerPoubelles :", err);
    }
}

// ============================================
// Badges colorés — niveau
// ============================================
function classCouleurNiveau(niveau) {
    if (!niveau) return "bg-secondary";
    if (niveau > 90) return "bg-danger";
    if (niveau > 70) return "bg-warning";
    return "bg-success";
}

function barreNiveau(niveau) {
    if (niveau === null || niveau === undefined) return "\u2014";
    var n   = parseFloat(niveau).toFixed(1);
    var cls = classCouleurNiveau(parseFloat(niveau));
    return '<div class="progress" style="min-width:80px">' +
        '<div class="progress-bar ' + cls + '" style="width:' + n + '%">' +
        n + '%</div></div>';
}

// ============================================
// Badges colorés — poids
// ============================================
function badgePoids(poids) {
    if (poids === null || poids === undefined) return "\u2014";
    var v = parseFloat(poids).toFixed(1);
    if (parseFloat(poids) > 15) return '<span class="badge bg-danger">' + v + ' kg</span>';
    if (parseFloat(poids) > 10) return '<span class="badge bg-warning text-dark">' + v + ' kg</span>';
    return v + " kg";
}

// ============================================
// Badges colorés — température
// ============================================
function badgeTemperature(temp) {
    if (temp === null || temp === undefined) return "\u2014";
    var v = parseFloat(temp).toFixed(1);
    if (parseFloat(temp) > 40) return '<span class="badge bg-danger">' + v + ' °C</span>';
    if (parseFloat(temp) > 30) return '<span class="badge bg-warning text-dark">' + v + ' °C</span>';
    return v + " °C";
}

// ============================================
// Badges colorés — humidité
// ============================================
function badgeHumidite(humidite) {
    if (humidite === null || humidite === undefined) return "\u2014";
    var v = parseFloat(humidite).toFixed(1);
    if (parseFloat(humidite) > 80) return '<span class="badge bg-danger">' + v + ' %</span>';
    if (parseFloat(humidite) > 60) return '<span class="badge bg-warning text-dark">' + v + ' %</span>';
    return v + " %";
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

// ============================================
// Formatage de date
// ============================================
function formatDate(dateStr) {
    if (!dateStr) return "\u2014";
    var d = new Date(dateStr);
    return d.toLocaleDateString("fr-FR") + " " +
        d.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });
}