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
 * Initialise la carte Leaflet centrée par défaut sur la Bretagne.
 * Ajoute les couches de tuiles (tileLayer) et configure les écouteurs d'événements
 * (déplacement, clic, fermeture de popup) nécessaires à l'interactivité.
 * 
 * @function initMap
 * @returns {void}
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
 * Calcule la zone géographique visible (Bounding Box) et le niveau de zoom actuel
 * de la carte, puis déclenche le chargement asynchrone des points correspondants.
 * 
 * @function updateMapPoints
 * @returns {void}
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
 * Récupère les référentiels de filtres (départements bretons et années) depuis l'API.
 * Alimente l'index départemental pour le décodage ultérieur des noms de départements.
 * 
 * @async
 * @function fetchReferentiel
 * @returns {Promise<void>}
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
 * Remplit les listes déroulantes de filtres.
 * Filtre les valeurs nulles, déduplique les années et ordonne les données.
 * 
 * @function populateFilters
 * @param {Object} data - L'objet contenant les référentiels.
 * @param {Array<{annee?: string|number}>} data.annees - Liste brute des années.
 * @param {Array<{code_dep: string|number, nom_departement: string}>} data.departements - Liste des départements.
 * @returns {void}
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
 * Récupère la liste des points de charge géolocalisés correspondant aux filtres et limites spécifiés.
 * Effectue un appel HTTP GET vers l'API '/pdc/map' puis transmet les résultats à la fonction render.
 * 
 * @async
 * @function fetchMapPoints
 * @param {Object} [filters={}] - Critères de filtrage et limites de coordonnées.
 * @param {string|number} [filters.annee] - Année choisie.
 * @param {string|number} [filters.code_dep] - Code de département choisi.
 * @param {number} [filters.zoom] - Niveau de zoom de la carte.
 * @param {number} [filters.min_lat] - Latitude minimale visible.
 * @param {number} [filters.max_lat] - Latitude maximale visible.
 * @param {number} [filters.min_lng] - Longitude minimale visible.
 * @param {number} [filters.max_lng] - Longitude maximale visible.
 * @param {boolean} [fitBounds=false] - Ajuste ou non les limites géographiques de la vue de la carte sur les points trouvés.
 * @returns {Promise<void>}
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
 * Génère le contenu HTML destiné à habiller la bulle d'information (popup) d'un marqueur.
 * Distingue le cas d'une station regroupant plusieurs bornes (zoom faible) et d'un point individuel (zoom élevé).
 * 
 * @param {Object} s - Les données de la borne ou station de recharge.
 * @param {string} s.nom_station - Le nom attribué à la station.
 * @param {string} s.adresse_station - L'adresse de la station.
 * @param {string} s.localite - La commune.
 * @param {string|number} s.dept - Le code du département.
 * @param {string|number} [s.annee] - L'année de mise en service.
 * @param {number} [s.puissance] - La puissance électrique.
 * @param {string} [s.type_prise] - Le type de prise.
 * @param {number} [s.count_pdc] - Le cas échéant, le nombre de points regroupés sous cette station.
 * @param {string|number} [s.id] - Identifiant unique de la borne individuelle.
 * @returns {string} Le code HTML formaté.
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
 * Construit un objet icône divIcon personnalisé de Leaflet comportant le SVG de l'éclair.
 * 
 * @param {boolean} active - Si vrai, applique la classe active (.is-active) pour surligner le marqueur.
 * @returns {L.DivIcon} L'objet icône de Leaflet configuré.
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
 * Sélectionne une borne spécifique : centre la vue cartographique avec survol fluide,
 * applique le style visuel actif sur son icône et ouvre sa bulle d'information.
 * 
 * @param {string|number} id - L'identifiant unique du point de charge.
 * @returns {void}
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
 * Désélectionne la borne active, lui réapplique son icône par défaut et vide le pointeur actif.
 * 
 * @returns {void}
*/
function deselectBorne() {
    if (activeId !== null && refs.has(activeId)) {
        refs.get(activeId).setIcon(makeIcon(false));
    }
    activeId = null;
}

/**
 * Nettoie la couche existante et dessine les nouveaux marqueurs géographiques sur la carte.
 * Si une borne précédemment active est à nouveau visible, elle est sélectionnée automatiquement.
 * Ajuste la vue cartographique si fitBounds est vrai.
 * 
 * @param {Array<Object>} stations - Liste des points de recharge retournés par le serveur.
 * @param {boolean} [fitBounds=false] - Si vrai, ajuste les limites de zoom de la carte sur l'ensemble des points.
 * @returns {void}
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
