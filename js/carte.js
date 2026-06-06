let map;
let deptsMap = {};
let markersLayer;
const refs = new Map();
let activeId = null;
let activeFilters = {
    annee: "",
    code_dep: ""
};

const BOLT_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M19 9h-5V3a1 1 0 0 0-.69-.95c-.41-.13-.86.01-1.12.36l-8 11a1 1 0 0 0-.08 1.04c.17.34.51.55.89.55h5v6a1 1 0 0 0 1 1c.31 0 .62-.15.81-.41l8-11a1 1 0 0 0 .08-1.04A.99.99 0 0 0 19 9"></path></svg>';

document.addEventListener("DOMContentLoaded", () => {
    fetchReferentiel();
    initMap();

    const searchBtn = document.querySelector(".search-btn");
    if (searchBtn) {
        searchBtn.addEventListener("click", (e) => {
            e.preventDefault();
            activeFilters.annee = document.getElementById("select-annee").value;
            activeFilters.code_dep = document.getElementById("select-departement").value;
            
            // Lors d'une recherche explicite, on récupère l'ensemble des points sans filtre de bounding box
            // pour pouvoir zoomer dessus et centrer la carte.
            fetchMapPoints({ annee: activeFilters.annee, code_dep: activeFilters.code_dep }, true);
        });
    }
});

/**
 * Initialise la carte Leaflet
 */
function initMap() {
    map = L.map('map', { scrollWheelZoom: true });

    markersLayer = L.layerGroup().addTo(map);

    // Écouteur sur le déplacement ou zoom
    map.on("moveend", () => {
        updateMapPoints();
    });

    // Écouteur de clic sur la carte pour déselectionner la borne active
    map.on("click", () => {
        deselectBorne();
    });

    // Écouteur de fermeture de popup pour déselectionner la borne active
    map.on("popupclose", (e) => {
        if (activeId !== null && refs.has(activeId)) {
            const activeMarker = refs.get(activeId);
            if (activeMarker && activeMarker.getPopup() === e.popup) {
                deselectBorne();
            }
        }
    });

    // Centrage initial sur la Bretagne (déclenchera le moveend et chargera les points)
    map.setView([48.2, -2.9], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles courtesy of <a href="https://hot.openstreetmap.org/">Humanitarian OpenStreetMap Team</a>',
        maxZoom: 19,
    }).addTo(map);
}

/**
 * Lance le chargement des points de la zone visible
 */
function updateMapPoints() {
    const zoom = map.getZoom();
    const bounds = map.getBounds();
    const southWest = bounds.getSouthWest();
    const northEast = bounds.getNorthEast();

    fetchMapPoints({
        annee: activeFilters.annee,
        code_dep: activeFilters.code_dep,
        zoom: zoom,
        min_lat: southWest.lat,
        max_lat: northEast.lat,
        min_lng: southWest.lng,
        max_lng: northEast.lng
    }, false);
}

/**
 * Récupère les référentiels (années d'installation et départements) depuis l'API
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
        console.error("Erreur lors de la récupération des référentiels:", error);
    }
}

/**
 * Remplit les listes déroulantes de filtres
 */
function populateFilters(data) {
    const selectAnnee = document.getElementById("select-annee");
    if (selectAnnee && Array.isArray(data.annees)) {
        selectAnnee.innerHTML = '<option value="">— Toutes les années —</option>';
        
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

    const selectDepartement = document.getElementById("select-departement");
    if (selectDepartement && Array.isArray(data.departements)) {
        selectDepartement.innerHTML = '<option value="">— Tous les départements —</option>';
        
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
 * Récupère les points géolocalisés depuis l'API avec les filtres et limites de coordonnées
 */
async function fetchMapPoints(filters = {}, fitBounds = false) {
    try {
        const queryParams = new URLSearchParams();
        if (filters.annee) queryParams.append("annee", filters.annee);
        if (filters.code_dep) queryParams.append("code_dep", filters.code_dep);
        if (filters.zoom !== undefined) queryParams.append("zoom", filters.zoom);
        if (filters.min_lat !== undefined) queryParams.append("min_lat", filters.min_lat);
        if (filters.max_lat !== undefined) queryParams.append("max_lat", filters.max_lat);
        if (filters.min_lng !== undefined) queryParams.append("min_lng", filters.min_lng);
        if (filters.max_lng !== undefined) queryParams.append("max_lng", filters.max_lng);

        const response = await fetch(`../api/request.php/pdc/map?${queryParams.toString()}`);
        if (!response.ok) {
            throw new Error("Erreur lors de la récupération des points de carte");
        }
        const stations = await response.json();
        render(stations, fitBounds);
    } catch (error) {
        console.error("Erreur lors de la récupération des points de carte:", error);
    }
}

/**
 * Retourne le contenu HTML de la bulle d'info (popup)
 */
function popupHTML(s) {
    const deptName = deptsMap[s.dept] || `Département ${s.dept}`;
    const anneeText = s.annee ? ` · ${s.annee}` : "";
    const puissanceText = s.puissance ? `${s.puissance} kW` : "Puissance inconnue";
    const typePriseText = s.type_prise ? ` (${s.type_prise})` : "";
    
    // Si c'est un point groupé représentant plusieurs bornes physiques à cette station
    if (s.count_pdc && s.count_pdc > 1) {
        return `
            <div class="borne-popup-loc">${s.nom_station || "Station sans nom"}</div>
            <div class="borne-popup-meta">${s.adresse_station || "Adresse non spécifiée"}</div>
            <div class="borne-popup-meta">${s.localite || "Localité inconnue"} (${deptName} - ${s.dept})${anneeText}</div>
            <span class="borne-popup-power" style="background:var(--vert-fonce);color:#ffffff;padding:4px 10px;border-radius:12px;font-weight:600;">${s.count_pdc} points de charge</span>
            <p style="font-size:11px;color:var(--texte3);margin-top:8px;">Zoomez sur la carte pour afficher les détails des bornes individuelles.</p>
        `;
    }

    return `
        <div class="borne-popup-loc">${s.nom_station || "Station sans nom"}</div>
        <div class="borne-popup-meta">${s.adresse_station || "Adresse non spécifiée"}</div>
        <div class="borne-popup-meta">${s.localite || "Localité inconnue"} (${deptName} - ${s.dept})${anneeText}</div>
        <span class="borne-popup-power">${puissanceText}${typePriseText}</span>
        <a class="borne-popup-link" href="detail.html?id_pdc=${s.id}">Voir le détail →</a>
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
 * Désélectionne la borne active : remet son icône par défaut et vide l'état actif
 */
function deselectBorne() {
    if (activeId !== null && refs.has(activeId)) {
        refs.get(activeId).setIcon(makeIcon(false));
    }
    activeId = null;
}

/**
 * Dessine les marqueurs sur la carte
 */
function render(stations, fitBounds = false) {
    // On conserve et réinitialise l'ID actif temporairement
    const prevActiveId = activeId;
    activeId = null;

    // On nettoie la couche (déclenchera popupclose, mais comme activeId est déjà null, l'écouteur sera ignoré)
    markersLayer.clearLayers();
    refs.clear();

    if (stations.length === 0) return;

    stations.forEach((s) => {
        if (s.lat && s.lng) {
            const isActive = (s.id === prevActiveId);
            const marker = L.marker([s.lat, s.lng], { icon: makeIcon(isActive) })
                .bindPopup(popupHTML(s))
                .addTo(markersLayer);
            
            marker.on('click', () => selectBorne(s.id));
            refs.set(s.id, marker);

            if (isActive) {
                activeId = s.id;
                // Ouvrir le popup après un court délai pour laisser Leaflet se stabiliser
                setTimeout(() => {
                    marker.openPopup();
                }, 50);
            }
        }
    });

    // Ajuster le zoom uniquement si demandé explicitement (ex: après clic sur filtrer)
    if (fitBounds && refs.size > 0) {
        const group = L.featureGroup([...refs.values()]);
        map.fitBounds(group.getBounds().pad(0.2));
    }
}
