'use strict';

async function requestPDCS() {
    try {
        const response = await fetch('../../utils/request.php/pdcs');
        const stations = await response.json();

        const tbody = document.getElementById('pdcs-table-body');
        
        tbody.innerHTML = ''; 

        stations.forEach(station => {
            const tr = document.createElement('tr');
        
            tr.innerHTML = `
                <td>${station.nom_station || ''}</td>
                <td>${station.amenageur || ''}</td>
                <td>${station.operateur || ''}</td>
                <td>${station.type_prise || ''}</td>
                <td>${station.commune || ''}</td>
                <td>${station.tarification || ''}</td>
                <td>
                    <a href="detail.php?id=${encodeURIComponent(station.id_station_itinerance)}"><button class="btn-view">Voir</button></a>
                    <a href="edit.php?id=${encodeURIComponent(station.id_station_itinerance)}"><button class="btn-edit">Éditer</button></a>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
        
    } catch (error) {
        console.error('Erreur:', error);
    }
}

async function requestPDC() {
    try {
        const response = await fetch('../../utils/request.php/pdc');
        const stations = await response.json();

        const tbody = document.getElementById('pdcs-table-body');
        
        tbody.innerHTML = ''; 

        stations.forEach(station => {
            const tr = document.createElement('tr');
        
            tr.innerHTML = `
                <td>${station.nom_station || ''}</td>
                <td>${station.amenageur || ''}</td>
                <td>${station.operateur || ''}</td>
                <td>${station.type_prise || ''}</td>
                <td>${station.commune || ''}</td>
                <td>${station.tarification || ''}</td>
                <td>
                    <a href="detail.php?id=${encodeURIComponent(station.id_station_itinerance)}"><button class="btn-view">Voir</button></a>
                    <a href="edit.php?id=${encodeURIComponent(station.id_station_itinerance)}"><button class="btn-edit">Éditer</button></a>
                </td>
            `;
            
            tbody.appendChild(tr);
        });
        
    } catch (error) {
        console.error('Erreur:', error);
    }
} 

requestPDCS();