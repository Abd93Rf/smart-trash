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

            resultat.data.forEach(function (m) {
                // Formater la date en "JJ/MM"
                var d = new Date(m.jour);
                jours.push(d.toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit" }));
                niveaux.push(m.moyenne_niveau);
                poids.push(m.moyenne_poids);
            });

            // Créer le graphique avec Chart.js
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
// Tableau : Classement des poubelles
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
                    "<td>" + (p.niveau_max || 0) + "%</td>" +
                    "<td>" + p.nb_alertes + "</td>";
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        console.error("Erreur chargement classement :", err);
    }
}

// Couleur selon le niveau
function couleurNiveau(niveau) {
    if (niveau > 70) return "bg-danger";
    if (niveau > 40) return "bg-warning";
    return "bg-success";
}
