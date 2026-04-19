// ============================================
// Smart Trash - Statistiques
// ============================================

document.addEventListener("DOMContentLoaded", function () {
    chargerMoyennes();
    chargerHeuresPointe();
    chargerClassement();
});

// ============================================
// Graphique : Moyennes par jour
// ============================================
async function chargerMoyennes() {
    try {
        var response = await fetch("/api/statistiques.php?type=moyennes");
        var resultat = await response.json();

        if (resultat.status === "success") {
            var jours = [];
            var niveaux = [];
            var poids = [];
            var humidites = [];

            resultat.data.forEach(function (m) {
                var d = new Date(m.jour);
                jours.push(d.toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit" }));
                niveaux.push(m.moyenne_niveau);
                poids.push(m.moyenne_poids);
                humidites.push(m.moyenne_humidite);
            });

            var ctx = document.getElementById("graphMoyennes").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: jours,
                    datasets: [
                        {
                            label: "Niveau moyen (%)",
                            data: niveaux,
                            backgroundColor: "rgba(255, 99, 132, 0.6)",
                            borderColor: "rgba(255, 99, 132, 1)",
                            borderWidth: 1
                        },
                        {
                            label: "Poids moyen (kg)",
                            data: poids,
                            backgroundColor: "rgba(54, 162, 235, 0.6)",
                            borderColor: "rgba(54, 162, 235, 1)",
                            borderWidth: 1
                        },
                        {
                            label: "Humidité moyenne (%)",
                            data: humidites,
                            backgroundColor: "rgba(75, 192, 192, 0.6)",
                            borderColor: "rgba(75, 192, 192, 1)",
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    } catch (err) {
        console.error("Erreur chargement moyennes :", err);
    }
}

// ============================================
// Graphique : Heures de pointe
// ============================================
async function chargerHeuresPointe() {
    try {
        var response = await fetch("/api/statistiques.php?type=heures_pointe");
        var resultat = await response.json();

        if (resultat.status === "success") {
            var heures = [];
            var niveaux = [];

            resultat.data.forEach(function (h) {
                heures.push(h.heure + "h");
                niveaux.push(h.moyenne_niveau);
            });

            var ctx = document.getElementById("graphHeures").getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: {
                    labels: heures,
                    datasets: [{
                        label: "Niveau moyen (%)",
                        data: niveaux,
                        borderColor: "rgba(255, 159, 64, 1)",
                        backgroundColor: "rgba(255, 159, 64, 0.2)",
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        }
    } catch (err) {
        console.error("Erreur chargement heures de pointe :", err);
    }
}

// ============================================
// Tableau : Classement enrichi des poubelles
// ============================================
async function chargerClassement() {
    try {
        var response = await fetch("/api/statistiques.php?type=classement");
        var resultat = await response.json();

        if (resultat.status === "success") {
            var tbody = document.getElementById("tableClassement");
            tbody.innerHTML = "";

            resultat.data.forEach(function (p, index) {
                var tr = document.createElement("tr");
                tr.innerHTML =
                    "<td><strong>" + (index + 1) + "</strong></td>" +
                    "<td>" + p.nom + "</td>" +
                    "<td>" +
                    '<div class="progress progress-niveau" style="min-width:80px">' +
                    '<div class="progress-bar ' + couleurNiveau(p.moyenne_niveau) + '" style="width:' + p.moyenne_niveau + '%">' +
                    p.moyenne_niveau + "%" +
                    "</div>" +
                    "</div>" +
                    "</td>" +
                    "<td>" + badgePoids(p.moyenne_poids) + "</td>" +
                    "<td>" + badgeTemperature(p.moyenne_temperature) + "</td>" +
                    "<td>" + badgeHumidite(p.moyenne_humidite) + "</td>" +
                    "<td>" + (p.vitesse_remplissage || 0) + " %/h</td>" +
                    "<td>" + badgeAlertes(p.nb_alertes) + "</td>" +
                    "<td>" + badgeScore(p.score) + "</td>";
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        console.error("Erreur chargement classement :", err);
    }
}

// ============================================
// Fonctions utilitaires d'affichage
// ============================================

function couleurNiveau(niveau) {
    if (niveau > 90) return "bg-danger";
    if (niveau > 70) return "bg-warning";
    return "bg-success";
}

function badgePoids(poids) {
    if (poids > 15) return '<span class="badge bg-danger">' + parseFloat(poids).toFixed(1) + ' kg</span>';
    if (poids > 10) return '<span class="badge bg-warning text-dark">' + parseFloat(poids).toFixed(1) + ' kg</span>';
    return parseFloat(poids).toFixed(1) + " kg";
}

function badgeTemperature(temp) {
    if (temp > 40) return '<span class="badge bg-danger">' + parseFloat(temp).toFixed(1) + ' °C</span>';
    if (temp > 30) return '<span class="badge bg-warning text-dark">' + parseFloat(temp).toFixed(1) + ' °C</span>';
    return parseFloat(temp).toFixed(1) + " °C";
}

function badgeHumidite(humidite) {
    if (!humidite) return "-";
    if (humidite > 80) return '<span class="badge bg-danger">' + parseFloat(humidite).toFixed(1) + ' %</span>';
    if (humidite > 60) return '<span class="badge bg-warning text-dark">' + parseFloat(humidite).toFixed(1) + ' %</span>';
    return parseFloat(humidite).toFixed(1) + " %";
}

function badgeAlertes(nb) {
    if (nb > 3) return '<span class="badge bg-danger">' + nb + '</span>';
    if (nb > 0) return '<span class="badge bg-warning text-dark">' + nb + '</span>';
    return '<span class="badge bg-success">0</span>';
}

function badgeScore(score) {
    if (score > 60) return '<span class="badge bg-danger" style="font-size: 0.9rem;">' + score + '</span>';
    if (score > 40) return '<span class="badge bg-warning text-dark" style="font-size: 0.9rem;">' + score + '</span>';
    return '<span class="badge bg-success" style="font-size: 0.9rem;">' + score + '</span>';
}