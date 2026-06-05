let activeFilters = {
    amenageur: "",
    type_prise: "",
    code_dep: ""
};

let deptsMap = {};

document.addEventListener("DOMContentLoaded", () => {
    fetchReferentiel();
    // Chargement initial (sans filtres)
    fetchPDCs(1, true);

    // Écouteur sur le bouton de recherche
    const searchBtn = document.querySelector(".search-btn");
    if (searchBtn) {
        searchBtn.addEventListener("click", (e) => {
            e.preventDefault();
            fetchPDCs(1, true); // Lancer la recherche et verrouiller les filtres actifs
        });
    }
});

/**
 * Récupère les données de référentiel (filtres) depuis l'API request.php
*/
async function fetchReferentiel() {
    try {
        const response = await fetch("../api/request.php/referentiel");
        if (!response.ok) {
            throw new Error("Erreur réseau lors du chargement des référentiels");
        }
        const data = await response.json();
        
        if (Array.isArray(data.departements)) {
            data.departements.forEach(d => {
                deptsMap[d.code_dep] = d.nom_departement;
            });
        }
        
        populateFilters(data);
    } catch (error) {
        console.error("Erreur lors de la récupération du référentiel:", error);
    }
}

/**
 * Remplit les éléments <select> de filtre avec les données récupérées, ordonnées et sans doublons
*/
function populateFilters(data) {
    // 1. Remplissage des Aménageurs
    const selectAmenageur = document.getElementById("select-amenageur");
    if (selectAmenageur && Array.isArray(data.amenageurs)) {
        selectAmenageur.innerHTML = '<option value="">— Tous —</option>';
        
        // Déduplication et tri par ordre alphabétique
        const uniqueAmenageurs = Array.from(
            new Set(data.amenageurs.map(item => item.nom_acteur ? item.nom_acteur.trim() : "").filter(Boolean))
        ).sort((a, b) => a.localeCompare(b));

        uniqueAmenageurs.forEach(name => {
            const opt = document.createElement("option");
            opt.value = name;
            opt.textContent = name;
            selectAmenageur.appendChild(opt);
        });
    }

    // 2. Remplissage des Types de prise
    const selectPrise = document.getElementById("select-prise");
    if (selectPrise && Array.isArray(data.types_prise)) {
        selectPrise.innerHTML = '<option value="">— Tous —</option>';

        // Déduplication et tri
        const uniquePrises = Array.from(
            new Set(data.types_prise.map(item => item.type_prise ? item.type_prise.trim() : "").filter(Boolean))
        ).sort((a, b) => a.localeCompare(b));

        uniquePrises.forEach(type => {
            const opt = document.createElement("option");
            opt.value = type;
            opt.textContent = type;
            selectPrise.appendChild(opt);
        });
    }

    // 3. Remplissage des Départements
    const selectDepartement = document.getElementById("select-departement");
    if (selectDepartement && Array.isArray(data.departements)) {
        selectDepartement.innerHTML = '<option value="">— Tous —</option>';

        // Tri par code de département
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

/**
 * Récupère les points de charge de manière paginée et filtrée
*/
async function fetchPDCs(page = 1, lockCurrentInputs = false) {
    try {
        if (lockCurrentInputs) {
            const selectAmenageur = document.getElementById("select-amenageur");
            const selectPrise = document.getElementById("select-prise");
            const selectDepartement = document.getElementById("select-departement");
            
            activeFilters.amenageur = selectAmenageur ? selectAmenageur.value : "";
            activeFilters.type_prise = selectPrise ? selectPrise.value : "";
            activeFilters.code_dep = selectDepartement ? selectDepartement.value : "";
        }
        
        // Construction des paramètres d'URL pour le filtrage à partir des filtres verrouillés
        const params = new URLSearchParams({
            page: page,
            amenageur: activeFilters.amenageur,
            type_prise: activeFilters.type_prise,
            code_dep: activeFilters.code_dep
        });

        const response = await fetch(`../api/request.php/pdc?${params.toString()}`);
        if (!response.ok) {
            throw new Error("Erreur réseau lors du chargement des points de charge");
        }
        const data = await response.json();
        
        // Mettre à jour le compteur de résultats
        const countEl = document.getElementById("results-count");
        if (countEl) {
            countEl.textContent = `${formatNumber(data.total)} bornes trouvées`;
        }
        
        // Rendre les lignes du tableau
        renderResultsTable(data.pdcs);
        
        // Rendre la pagination
        renderPager(data.page, data.pages);
        
    } catch (error) {
        console.error("Erreur lors de la récupération des points de charge:", error);
    }
}

/**
 * Formate une date AAAA-MM-JJ en MM/AAAA (Mois et Année)
*/
function formatMoisAnnee(dateStr) {
    if (!dateStr) return "Non renseignée";
    const parts = dateStr.split("-");
    if (parts.length >= 2) {
        return `${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

/**
 * Génère le tableau HTML avec les résultats
*/
function renderResultsTable(pdcs) {
    const tbody = document.getElementById("results-body");
    if (!tbody) return;
    
    tbody.innerHTML = "";
    
    if (!pdcs || pdcs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--texte3)">Aucun résultat</td></tr>`;
        return;
    }
    
    pdcs.forEach((pdc, index) => {
        const tr = document.createElement("tr");
        
        // Délai d'animation pour effet fluide
        tr.style.animationDelay = `${Math.min((index + 1) * 0.05, 1)}s`;
        
        const dateRaw = pdc.date_mise_en_service || "";
        const dateFormatted = formatMoisAnnee(dateRaw);
        const typePrise = escapeHtml(pdc.type_prise || "Inconnu");
        const puissance = pdc.puissance ? `${pdc.puissance} kW` : "Non renseignée";
        const commune = escapeHtml(pdc.commune || "");
        const codeDepRaw = pdc.code_dep ? pdc.code_dep.toString() : "";
        const codeDep = escapeHtml(codeDepRaw);
        const deptName = escapeHtml(deptsMap[codeDepRaw] || "");
        
        const locationText = (commune && codeDep && deptName) ? `${commune} (${deptName} - ${codeDep})` : (commune || deptName || codeDep || "Non renseignée");
        
        const idPdc = encodeURIComponent(pdc.id_pdc);
        const priseParam = encodeURIComponent(pdc.type_prise || "");
        
        tr.innerHTML = `
            <td>${dateFormatted}</td>
            <td><span class="tag-prise">${typePrise}</span></td>
            <td>${puissance}</td>
            <td>${locationText}</td>
            <td><a href="detail.html?id_pdc=${idPdc}&type_prise=${priseParam}" class="link-detail">Voir le détail →</a></td>
        `;
        
        tbody.appendChild(tr);
    });
}

/**
 * Génère la pagination HTML identique à la logique du back-office (liste.php)
*/
function renderPager(currentPage, totalPages) {
    const pager = document.getElementById("results-pager");
    if (!pager) return;
    
    pager.innerHTML = "";
    
    if (totalPages <= 1) return;
    
    // Bouton Précédent (←)
    const prevBtn = document.createElement("button");
    prevBtn.className = "pager-btn";
    prevBtn.textContent = "←";
    if (currentPage > 1) {
        prevBtn.addEventListener("click", () => fetchPDCs(currentPage - 1));
    } else {
        prevBtn.disabled = true;
    }
    pager.appendChild(prevBtn);
    
    // Calcul de la plage de pages à afficher (exactement comme dans liste.php)
    let range = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) range.push(i);
    } else {
        range.push(1);
        if (currentPage > 3) range.push("...");
        
        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);
        for (let p = start; p <= end; p++) {
            range.push(p);
        }
        
        if (currentPage < totalPages - 2) range.push("...");
        range.push(totalPages);
    }
    
    // Rendu des boutons de page
    range.forEach(p => {
        if (p === "...") {
            const dots = document.createElement("span");
            dots.className = "pager-dots";
            dots.textContent = "…";
            pager.appendChild(dots);
        } else {
            const btn = document.createElement("button");
            btn.className = "pager-btn";
            if (p === currentPage) {
                btn.classList.add("active");
            }
            btn.textContent = p;
            btn.addEventListener("click", () => fetchPDCs(p));
            pager.appendChild(btn);
        }
    });
    
    // Bouton Suivant (→)
    const nextBtn = document.createElement("button");
    nextBtn.className = "pager-btn";
    nextBtn.textContent = "→";
    if (currentPage < totalPages) {
        nextBtn.addEventListener("click", () => fetchPDCs(currentPage + 1));
    } else {
        nextBtn.disabled = true;
    }
    pager.appendChild(nextBtn);
}

/**
 * Échappe le HTML pour éviter les injections XSS
*/
function escapeHtml(str) {
    if (!str) return "";
    return str
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/**
 * Formate un nombre avec un espace pour les milliers
*/
function formatNumber(num) {
    if (num === null || num === undefined) return "-";
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}
