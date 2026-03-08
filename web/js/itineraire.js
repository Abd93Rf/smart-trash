// ============================================
// Smart Trash - Itinéraire optimisé
// ============================================

// Variable globale pour la carte
var carte;

document.addEventListener("DOMContentLoaded", function () {
    // Initialiser la carte Leaflet (centrée sur Tunis)
    carte = L.map("map").setView([48.9340, 2.3570], 15);

    // Ajouter le fond de carte OpenStreetMap
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap"
    }).addTo(carte);

    // Charger l'itinéraire
    chargerItineraire();
});

// ============================================
// Charger l'itinéraire depuis l'API
// ============================================
async function chargerItineraire() {
    try {
        var response = await fetch("/api/itineraire.php");
        var resultat = await response.json();

        if (resultat.status === "success") {
            var data = resultat.data;

            // Mettre à jour les cartes de résumé
            document.getElementById("nbPoubelles").textContent = data.nb_poubelles;
            document.getElementById("distanceTotale").textContent = data.distance_totale;
            document.getElementById("tempsEstime").textContent = data.temps_estime;

            // Si aucune poubelle à collecter
            if (data.ordre_passage.length === 0) {
                document.getElementById("ordrePassage").innerHTML =
                    '<div class="alert alert-success">' +
                    '<i class="bi bi-check-circle"></i> Toutes les poubelles sont en dessous de 70%. Aucune collecte nécessaire.' +
                    '</div>';
                return;
            }

            // Afficher les marqueurs sur la carte
            afficherMarqueurs(data.ordre_passage);

            // Afficher l'ordre de passage dans la liste
            afficherOrdrePassage(data.ordre_passage);
        }
    } catch (err) {
        console.error("Erreur chargement itinéraire :", err);
        document.getElementById("ordrePassage").innerHTML =
            '<div class="alert alert-danger">Erreur de connexion au serveur.</div>';
    }
}

// ============================================
// Afficher les marqueurs et le trajet sur la carte
// ============================================
function afficherMarqueurs(poubelles) {
    var coordonnees = [];

    poubelles.forEach(function (p, index) {
        var lat = p.latitude;
        var lng = p.longitude;
        coordonnees.push([lat, lng]);

        // Créer un marqueur numéroté
        var icone = L.divIcon({
            className: "custom-marker",
            html: '<div style="background-color: #dc3545; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">' + (index + 1) + '</div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        // Ajouter le marqueur avec popup
        L.marker([lat, lng], { icon: icone })
            .addTo(carte)
            .bindPopup(
                "<strong>" + (index + 1) + ". " + p.nom + "</strong><br>" +
                (p.adresse || "") + "<br>" +
                "Niveau : " + Math.round(p.niveau) + "%"
            );
    });

    // Tracer la ligne du trajet
    if (coordonnees.length > 1) {
        L.polyline(coordonnees, {
            color: "#dc3545",
            weight: 3,
            opacity: 0.7,
            dashArray: "10, 10"
        }).addTo(carte);
    }

    // Ajuster le zoom pour voir tous les marqueurs
    if (coordonnees.length > 0) {
        carte.fitBounds(coordonnees, { padding: [30, 30] });
    }
}

// ============================================
// Afficher l'ordre de passage dans la sidebar
// ============================================
function afficherOrdrePassage(poubelles) {
    var conteneur = document.getElementById("ordrePassage");
    conteneur.innerHTML = "";

    poubelles.forEach(function (p, index) {
        var div = document.createElement("div");
        div.className = "d-flex align-items-center mb-3 p-2 border rounded";
        div.innerHTML =
            '<div style="background-color: #dc3545; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; flex-shrink: 0;">' +
                (index + 1) +
            "</div>" +
            "<div>" +
                "<strong>" + p.nom + "</strong><br>" +
                '<small class="text-muted">' + (p.adresse || "Pas d'adresse") + "</small><br>" +
                '<span class="badge bg-danger">Niveau : ' + Math.round(p.niveau) + "%</span>" +
            "</div>";
        conteneur.appendChild(div);

        // Ajouter une flèche entre les étapes (sauf la dernière)
        if (index < poubelles.length - 1) {
            var fleche = document.createElement("div");
            fleche.className = "text-center text-muted mb-2";
            fleche.innerHTML = '<i class="bi bi-arrow-down"></i>';
            conteneur.appendChild(fleche);
        }
    });
}
