document.addEventListener("DOMContentLoaded", () => {
    fetchPDCDetail();
});

/**
 * Récupère les détails d'un point de charge spécifique via l'API et les affiche dans la page.
 * Analyse les paramètres de la barre d'adresse (query string) pour extraire 'id_pdc'.
 * Envoie une requête HTTP GET asynchrone vers '../api/request.php/pdc/detail?id_pdc=...'.
 * Renseigne les différents champs d'information du DOM (Aménageur, Puissance, Prise, Paiement, etc.).
 * Affiche le message d'erreur si l'identifiant est absent ou en cas d'échec de la requête.
 * 
 * @async
 * @returns {Promise<void>}
*/
async function fetchPDCDetail() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const idPdc = urlParams.get("id_pdc");

        if (!idPdc) {
            showError();
            return;
        }

        const params = new URLSearchParams({
            id_pdc: idPdc
        });

        const response = await fetch(`../api/request.php/pdc/detail?${params.toString()}`);
        if (!response.ok) {
            throw new Error("Erreur lors de la récupération du détail");
        }

        const data = await response.json();
        if (!data) {
            showError();
            return;
        }

        // Remplissage du DOM
        document.getElementById("det-nom-station").textContent = data.nom_station || "Non renseigné";
        document.getElementById("det-id-pdc-text").textContent = `ID PDC : ${data.id_pdc}`;
        
        // Aménageur & Opérateur
        document.getElementById("det-amenageur").textContent = data.amenageur || "Non renseigné";
        document.getElementById("det-siren").textContent = data.siren_amenageur || "Non renseigné";
        document.getElementById("det-operateur").textContent = data.operateur || "Non renseigné";
        document.getElementById("det-contact").textContent = data.contact_operateur || "Non renseigné";

        // Caractéristiques techniques
        document.getElementById("det-type-prise").textContent = data.type_prise || "Non renseigné";
        document.getElementById("det-puissance").textContent = data.puissance ? `${data.puissance} kW` : "Non renseignée";
        document.getElementById("det-cable").textContent = data.cable_t2_attache ? "Oui" : "Non";

        // Localisation
        document.getElementById("det-latitude").textContent = data.latitude || "Non renseignée";
        document.getElementById("det-longitude").textContent = data.longitude || "Non renseignée";
        document.getElementById("det-commune").textContent = data.commune || "Non renseignée";
        document.getElementById("det-departement").textContent = data.departement || "Non renseigné";

        // Paiement
        document.getElementById("det-tarification").textContent = data.tarification || "Non renseignée";
        document.getElementById("det-types-paiement").textContent = data.types_paiement || "Aucun moyen spécifié";
        document.getElementById("det-gratuit").textContent = data.gratuit ? "Oui" : "Non";

        // Afficher la carte de détails
        document.getElementById("detail-card").style.display = "block";

    } catch (error) {
        console.error("Erreur:", error);
        showError();
    }
}

/**
 * Gère l'affichage des erreurs sur l'interface utilisateur.
 * Affiche le conteneur du message d'erreur et cache la carte détaillée de la station.
 * 
 * @returns {void}
*/
function showError() {
    const errorEl = document.getElementById("error-message");
    if (errorEl) {
        errorEl.style.display = "block";
    }
    const cardEl = document.getElementById("detail-card");
    if (cardEl) {
        cardEl.style.display = "none";
    }
}
