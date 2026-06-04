document.addEventListener("DOMContentLoaded", () => {
    fetchReferentiel();
});

/**
 * Récupère les référentiels (années d'installation et départements) depuis l'API
 */
async function fetchReferentiel() {
    try {
        const response = await fetch("../../api/request.php/referentiel");
        if (!response.ok) {
            throw new Error("Erreur réseau lors du chargement des référentiels");
        }
        const data = await response.json();
        populateFilters(data);
    } catch (error) {
        console.error("Erreur lors de la récupération des référentiels:", error);
    }
}

/**
 * Remplit les listes déroulantes de filtres
 */
function populateFilters(data) {
    // 1. Remplissage des Années d'installation
    const selectAnnee = document.getElementById("select-annee");
    if (selectAnnee && Array.isArray(data.annees)) {
        selectAnnee.innerHTML = '<option value="">— Toutes les années —</option>';
        
        // Déduplication, nettoyage des valeurs nulles/invalides et tri croissant
        const uniqueYears = Array.from(
            new Set(data.annees.map(item => item.annee ? parseInt(item.annee, 10) : null).filter(Boolean))
        ).sort((a, b) => a - b);

        uniqueYears.forEach(year => {
            const opt = document.createElement("option");
            opt.value = year;
            opt.textContent = year;
            selectAnnee.appendChild(opt);
        });
    }

    // 2. Remplissage des Départements
    const selectDepartement = document.getElementById("select-departement");
    if (selectDepartement && Array.isArray(data.departements)) {
        selectDepartement.innerHTML = '<option value="">— Tous les départements —</option>';
        
        // Tri croissant par numéro de département
        const sortedDeps = [...data.departements].sort((a, b) => {
            return a.code_dep.toString().localeCompare(b.code_dep.toString(), undefined, { numeric: true, sensitivity: 'base' });
        });

        sortedDeps.forEach(item => {
            if (item.code_dep && item.nom_departement) {
                const opt = document.createElement("option");
                opt.value = item.code_dep;
                opt.textContent = `${item.code_dep} — ${item.nom_departement}`;
                selectDepartement.appendChild(opt);
            }
        });
    }
}
