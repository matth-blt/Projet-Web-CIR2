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
        amenageursEl.textContent = data.total_amenageurs;
    }

    // 2. Types de prise
    const prisesEl = document.getElementById("stat-prises");
    if (prisesEl) {
        prisesEl.textContent = data.total_prises;
    }

    // 3. Départements (22, 29, 35, 56)
    if (data.departments && Array.isArray(data.departments)) {
        data.departments.forEach(dep => {
            const num = dep.numero_departement;
            const count = dep.nombre_points_de_charge;
            const el = document.getElementById(`stat-dep-${num}`);
            if (el) {
                el.textContent = count;
            }
        });
    }

    // 4. Rendu du graphique Hero
    if (data.pdc_par_annee && Array.isArray(data.pdc_par_annee)) {
        renderHeroChart(data.pdc_par_annee);
    }

    // 5. Rendu du tableau par année et département
    if (data.pdc_par_annee_departement && Array.isArray(data.pdc_par_annee_departement)) {
        renderStatsTable(data.pdc_par_annee_departement);
    }
}

/**
 * Génère le graphique linéaire heroChart avec Chart.js
 */
function renderHeroChart(pdcParAnnee) {
    const canvas = document.getElementById('heroChart');
    if (!canvas) return;
    // Détruire l'instance existante si elle existe déjà sur ce canvas pour éviter l'erreur de réutilisation
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }

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
 * Génère le tableau avec les points de charge par année et par département
 */
function renderStatsTable(pdcParAnneeDep) {
    const tbody = document.getElementById('tableGroupedStatsBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    // 1. Extraire les départements uniques (code + nom) de manière dynamique
    const departmentsMap = new Map();
    pdcParAnneeDep.forEach(item => {
        if (item.numero_departement) {
            const code = item.numero_departement.toString();
            const name = item.nom_departement || `Département ${code}`;
            if (!departmentsMap.has(code)) {
                departmentsMap.set(code, name);
            }
        }
    });

    // Trier les codes de département pour un affichage ordonné
    const sortedDepCodes = Array.from(departmentsMap.keys()).sort((a, b) => {
        return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
    });

    // 2. Mettre à jour l'en-tête du tableau (les colonnes) de manière dynamique
    const theadTr = document.querySelector('#tableGroupedStats thead tr');
    if (theadTr) {
        theadTr.innerHTML = '<th>Année</th>';
        sortedDepCodes.forEach(code => {
            const name = departmentsMap.get(code);
            const th = document.createElement('th');
            th.textContent = `${name} (${code})`;
            theadTr.appendChild(th);
        });
    }

    // 3. Définir les années de manière dynamique à partir des données
    const yearsSet = new Set();
    pdcParAnneeDep.forEach(item => {
        if (item.annee) {
            yearsSet.add(item.annee.toString());
        }
    });
    const YEARS = Array.from(yearsSet).sort();

    // 4. Générer les lignes du tableau
    YEARS.forEach(year => {
        const tr = document.createElement('tr');
        
        // Colonne Année
        const tdYear = document.createElement('td');
        tdYear.innerHTML = `<strong>${year}</strong>`;
        tr.appendChild(tdYear);

        // Colonnes Départements
        sortedDepCodes.forEach(code => {
            const match = pdcParAnneeDep.find(item => 
                item.annee.toString() === year && 
                item.numero_departement.toString() === code
            );
            const count = match ? parseInt(match.nombre_points_de_charge, 10) || 0 : 0;
            
            const tdCount = document.createElement('td');
            tdCount.textContent = count;
            tr.appendChild(tdCount);
        });

        tbody.appendChild(tr);
    });
}
