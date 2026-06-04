let map;
let markersLayer;
const refs = new Map(); // id -> marker
let activeId = null;

const DEPTS = { 22: "Côtes-d'Armor", 29: "Finistère", 35: "Ille-et-Vilaine", 56: "Morbihan" };
const BOLT_SVG = '<svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>';

document.addEventListener("DOMContentLoaded", () => {
    fetchReferentiel();
    initMap();
    fetchMapPoints();

    // Écouteur sur le bouton Afficher
    const searchBtn = document.querySelector(".search-btn");
    if (searchBtn) {
        searchBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const year = document.getElementById("select-annee").value;
            const dept = document.getElementById("select-departement").value;
            fetchMapPoints({ annee: year, code_dep: dept });
        });
    }
});

/**
 * Initialise la carte Leaflet
 */
function initMap() {
    // Initialisation centrée sur la Bretagne
    map = L.map('map', { scrollWheelZoom: true }).setView([48.2, -2.9], 8);

    // Fond de carte CartoDB Positron (données © OpenStreetMap) : clair et minimal
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19,
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
}

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

/**
 * Récupère les points géolocalisés de l'API avec les filtres sélectionnés
 */
async function fetchMapPoints(filters = {}) {
    try {
        const queryParams = new URLSearchParams();
        if (filters.annee) queryParams.append("annee", filters.annee);
        if (filters.code_dep) queryParams.append("code_dep", filters.code_dep);

        const response = await fetch(`../../api/request.php/pdc/map?${queryParams.toString()}`);
        if (!response.ok) {
            throw new Error("Erreur lors de la récupération des points de carte");
        }
        const bornes = await response.json();
        render(bornes);
    } catch (error) {
        console.error("Erreur lors de la récupération des points de carte:", error);
    }
}

/**
 * Construit le contenu HTML d'une bulle d'info (popup)
 */
function popupHTML(b) {
    const deptName = DEPTS[b.dept] || `Département ${b.dept}`;
    const anneeText = b.annee ? ` · ${b.annee}` : "";
    const puissanceText = b.puissance ? `${b.puissance} kW` : "Puissance inconnue";
    
    // Le lien vers le détail doit inclure l'id_pdc et le type_prise pour detail.js
    const typePriseParam = b.type_prise ? `&type_prise=${encodeURIComponent(b.type_prise)}` : "";
    
    return `
        <div class="borne-popup-loc">${b.localite || "Localité inconnue"}</div>
        <div class="borne-popup-meta">${deptName} (${b.dept})${anneeText}</div>
        <span class="borne-popup-power">${puissanceText}</span>
        <a class="borne-popup-link" href="detail.html?id_pdc=${b.id}${typePriseParam}">Voir le détail →</a>
    `;
}

/**
 * Crée l'icône de marqueur personnalisé
 */
function makeIcon(active) {
    return L.divIcon({
        className: '',
        html: `<div class="borne-marker${active ? ' is-active' : ''}">${BOLT_SVG}</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -16],
    });
}

/**
 * Sélectionne une borne : centre la carte et ouvre la bulle d'info
 */
function selectBorne(id) {
    if (activeId !== null && refs.has(activeId)) {
        refs.get(activeId).setIcon(makeIcon(false));
    }
    activeId = id;
    const marker = refs.get(id);
    if (marker) {
        marker.setIcon(makeIcon(true));
        map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 11), { duration: 0.6 });
        marker.openPopup();
    }
}

/**
 * Dessine les marqueurs sur la carte
 */
function render(bornes) {
    markersLayer.clearLayers();
    refs.clear();
    activeId = null;

    if (bornes.length === 0) return;

    bornes.forEach((b) => {
        if (b.lat && b.lng) {
            const marker = L.marker([b.lat, b.lng], { icon: makeIcon(false) })
                .bindPopup(popupHTML(b))
                .addTo(markersLayer);
            
            marker.on('click', () => selectBorne(b.id));
            refs.set(b.id, marker);
        }
    });

    // Ajuster le zoom pour contenir tous les marqueurs trouvés
    if (refs.size > 0) {
        const group = L.featureGroup([...refs.values()]);
        map.fitBounds(group.getBounds().pad(0.2));
    }
}
