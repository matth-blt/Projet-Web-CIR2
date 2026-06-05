let map;
let deptsMap = {};
let markersLayer;
const refs = new Map();
let activeId = null;

const BOLT_SVG = '<svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>';

document.addEventListener("DOMContentLoaded", () => {
    fetchReferentiel();
    initMap();
    fetchMapPoints();

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

    L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles courtesy of <a href="https://hot.openstreetmap.org/">Humanitarian OpenStreetMap Team</a>',
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

        if (Array.isArray(data.departements)) {
            data.departements.forEach(d => {
                deptsMap[d.code_dep] = d.nom_departement;
            });
        }

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
 * Récupère les stations géolocalisées de l'API avec les filtres sélectionnés
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
        const stations = await response.json();
        render(stations);
    } catch (error) {
        console.error("Erreur lors de la récupération des points de carte:", error);
    }
}

/**
 * Construit le contenu HTML d'une bulle d'info (popup) pour une station
*/
function popupHTML(s) {
    const deptName = deptsMap[s.dept];
    const anneeText = s.annee ? ` · ${s.annee}` : "";
    const nbrPdcText = s.nbr_pdc > 1 ? `${s.nbr_pdc} points de charge` : `${s.nbr_pdc} point de charge`;
    
    return `
        <div class="borne-popup-loc">${s.nom_station || "Station sans nom"}</div>
        <div class="borne-popup-meta">${s.adresse_station || "Adresse non spécifiée"}</div>
        <div class="borne-popup-meta">${s.localite || "Localité inconnue"} (${deptName} - ${s.dept})${anneeText}</div>
        <span class="borne-popup-power">${nbrPdcText}</span>
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
 * Sélectionne une station : centre la carte et ouvre la bulle d'info
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
function render(stations) {
    markersLayer.clearLayers();
    refs.clear();
    activeId = null;

    if (stations.length === 0) return;

    stations.forEach((s) => {
        if (s.lat && s.lng) {
            const marker = L.marker([s.lat, s.lng], { icon: makeIcon(false) })
                .bindPopup(popupHTML(s))
                .addTo(markersLayer);
            
            marker.on('click', () => selectBorne(s.id));
            refs.set(s.id, marker);
        }
    });

    // Ajuster le zoom pour contenir tous les marqueurs trouvés
    if (refs.size > 0) {
        const group = L.featureGroup([...refs.values()]);
        map.fitBounds(group.getBounds().pad(0.2));
    }
}
