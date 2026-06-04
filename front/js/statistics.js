document.addEventListener("DOMContentLoaded", () => {
    fetchStats();
});

/**
 * Récupère les données de statistiques depuis l'API request.php
*/
async function fetchStats() {
    try {
        const response = await fetch("../../api/request.php/stats");
        if (!response.ok) {
            throw new Error("Erreur réseau lors du chargement des statistiques");
        }        
        const data = await response.json();
        updateStatsUI(data);
    } catch (error) {
        console.error("Erreur lors de la récupération des stats:", error);
        const targets = ["stat-total-records", "stat-amenageurs", "stat-prises", "stat-dep-22", "stat-dep-29", "stat-dep-35", "stat-dep-56"];
        targets.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = "N/A";
            }
        });
    }
}

/**
 * Met à jour le DOM avec les données récupérées
*/
function updateStatsUI(data) {
    // 1. Aménageurs
    const amenageursEl = document.getElementById("stat-amenageurs");
    if (amenageursEl) {
        amenageursEl.textContent = formatNumber(data.total_amenageurs);
    }

    // 2. Types de prise
    const prisesEl = document.getElementById("stat-prises");
    if (prisesEl) {
        prisesEl.textContent = formatNumber(data.total_prises);
    }

    // 3. Départements (22, 29, 35, 56)
    if (data.departments && Array.isArray(data.departments)) {
        data.departments.forEach(dep => {
            const num = dep.numero_departement;
            const count = dep.nombre_points_de_charge;
            const el = document.getElementById(`stat-dep-${num}`);
            if (el) {
                el.textContent = formatNumber(count);
            }
        });
    }

    // 4. Rendu du graphique Hero
    if (data.pdc_par_annee && Array.isArray(data.pdc_par_annee)) {
        renderHeroChart(data.pdc_par_annee);
    }

    // 5. Rendu du graphique par année et département
    if (data.pdc_par_annee_departement && Array.isArray(data.pdc_par_annee_departement)) {
        renderGroupedBarChart(data.pdc_par_annee_departement);
    }
}

/**
 * Génère le graphique linéaire heroChart avec Chart.js
 */
function renderHeroChart(pdcParAnnee) {
    const canvas = document.getElementById('heroChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    
    // Extraction des années et du nombre de points de charge
    const labels = pdcParAnnee.map(item => item.annee.toString());
    const data = pdcParAnnee.map(item => parseInt(item.nombre_points_de_charge, 10));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Bornes mises en service',
                data: data,
                borderColor: '#4caf7d',
                backgroundColor: 'rgba(76,175,125,0.12)',
                pointBackgroundColor: '#4caf7d',
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10,46,34,0.95)',
                    titleColor: '#ffffff',
                    bodyColor: 'rgba(255,255,255,0.7)',
                    borderColor: 'rgba(76,175,125,0.4)',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: item => '  ' + item.formattedValue + ' bornes',
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.07)' },
                    ticks: { color: 'rgba(255,255,255,0.45)', font: { family: 'DM Sans', size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.07)' },
                    ticks: { color: 'rgba(255,255,255,0.45)', font: { family: 'DM Sans', size: 11 }, precision: 0 },
                }
            }
        }
    });
}
/**
 * Génère le graphique à barres groupées chartGroupedBar avec Chart.js
 */
function renderGroupedBarChart(pdcParAnneeDep) {
    const canvas = document.getElementById('chartGroupedBar');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // 1. Définir les années de manière dynamique à partir des données
    const yearsSet = new Set();
    pdcParAnneeDep.forEach(item => {
        if (item.annee) {
            yearsSet.add(item.annee.toString());
        }
    });
    const YEARS = Array.from(yearsSet).sort();

    // Si aucune année trouvée, par défaut on prend 2018-2026
    if (YEARS.length === 0) {
        for (let y = 2018; y <= 2026; y++) YEARS.push(y.toString());
    }

    // 2. Préparer les données pour chaque département
    const depCodes = ['22', '29', '35', '56'];
    const depData = {
        '22': [],
        '29': [],
        '35': [],
        '56': []
    };

    YEARS.forEach(year => {
        depCodes.forEach(code => {
            const match = pdcParAnneeDep.find(item => 
                item.numero_departement.toString() === code && 
                item.annee.toString() === year
            );
            depData[code].push(match ? parseInt(match.nombre_points_de_charge, 10) : 0);
        });
    });

    // 3. Palette dégradé de verts cohérente avec les cartes (fournie dans l'index.html téléchargé)
    const DEP_COLORS = {
        '22': '#1a3a2a',  // vert-fonce
        '29': '#2d5c42',  // vert-moyen
        '35': '#4caf7d',  // vert-accent
        '56': '#9ad9bd',  // vert clair
    };

    // 4. Initialisation du graphique Chart.js
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: YEARS,
            datasets: [
                { label: "Côtes-d'Armor (22)", data: depData['22'], backgroundColor: DEP_COLORS['22'], borderRadius: 4, borderSkipped: false, maxBarThickness: 22 },
                { label: 'Finistère (29)', data: depData['29'], backgroundColor: DEP_COLORS['29'], borderRadius: 4, borderSkipped: false, maxBarThickness: 22 },
                { label: 'Ille-et-Vilaine (35)', data: depData['35'], backgroundColor: DEP_COLORS['35'], borderRadius: 4, borderSkipped: false, maxBarThickness: 22 },
                { label: 'Morbihan (56)', data: depData['56'], backgroundColor: DEP_COLORS['56'], borderRadius: 4, borderSkipped: false, maxBarThickness: 22 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1a3a2a',
                    titleColor: '#ffffff',
                    bodyColor: 'rgba(255,255,255,0.75)',
                    borderColor: 'rgba(76,175,125,0.35)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    usePointStyle: true,
                    callbacks: {
                        title: items => 'Année ' + items[0].label,
                        label: item => '  ' + item.dataset.label + ' : ' + item.formattedValue + ' PDC',
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: '#d8e4dc' },
                    ticks: { color: '#7a8c80', font: { family: 'DM Sans', size: 12 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#e8efe9' },
                    border: { display: false },
                    ticks: { color: '#7a8c80', font: { family: 'DM Sans', size: 12 }, precision: 0 },
                }
            }
        }
    });
}

/**
 * Formate un nombre avec un espace pour les milliers (ex : 24377 -> 24 377)
 */
function formatNumber(num) {
    if (num === null || num === undefined) return "-";
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}
