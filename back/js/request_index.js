'use strict';

async function requestPDCS() {
    try {
        const response = await fetch('../../utils/request.php/pdcs?accueil=true');
        const pdcs = await response.json();

        const tbody = document.getElementById('pdcs-table-body');
        
        tbody.innerHTML = ''; 

        pdcs.forEach(pdc => {
            const tr = document.createElement('tr');
        
            tr.innerHTML = `
                <td>${pdc.nom_station || ''}</td>
                <td>${pdc.amenageur || ''}</td>
                <td>${pdc.operateur || ''}</td>
                <td>${pdc.type_prise || ''}</td>
                <td>${pdc.commune || ''}</td>
                <td>${pdc.tarification || ''}</td>
                <td>
                    <a href="detail.php?id_pdc=${encodeURIComponent(pdc.id_pdc)}&type_prise=${encodeURIComponent(pdc.type_prise)}"><button class="btn-view">Voir</button></a>
                    <a href="edit.php?id=${encodeURIComponent(pdc.id_pdc)}"><button class="btn-edit">Éditer</button></a>
                </td>
            `;

            tbody.appendChild(tr);
        });
        
    } catch (error) {
        console.error('Erreur:', error);
    }
}

if (document.getElementById('pdcs-table-body')) {
    requestPDCS();
}
