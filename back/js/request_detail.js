'use strict';

async function requestPDC(id_pdc, type_prise) {
    try {
        const response = await fetch(`../../utils/request.php/pdcs?id_pdc=${id_pdc}&type_prise=${type_prise}`, {
            method: 'GET'
        });
        const pdcs = await response.json();
        const pdc = pdcs[0];

        if (!pdc) return;

        const header = document.querySelector('.detail-card-header');
        if (header) {
            header.innerHTML = `
                <div>
                    <h2>${pdc.nom_station || 'Non renseigné'}</h2>
                    <p>ID : ${pdc.id_pdc}</p>
                </div>
                <!-- TODO : adapter le lien vers l'édition si besoin -->
                <a href="/php/edit.php?id=${encodeURIComponent(pdc.id_pdc)}"><button class="btn-edit">Modifier</button></a>
            `;
        }

        const body = document.querySelector('.detail-card-body');
        if (body) {
            body.innerHTML = `
                <div class="detail-group">
                    <div class="detail-group-title">Aménageur  Opérateur</div>
                    <div class="detail-grid-2">
                        <div class="detail-field"><span class="detail-key">Aménageur</span><span class="detail-val">${pdc.amenageur || 'Non renseigné'}</span></div>
                        <div class="detail-field"><span class="detail-key">SIREN</span><span class="detail-val">${pdc.siren_amenageur || 'Non renseigné'}</span></div>
                        <div class="detail-field"><span class="detail-key">Opérateur</span><span class="detail-val">${pdc.operateur || 'Non renseigné'}</span></div>
                        <div class="detail-field"><span class="detail-key">Contact</span><span class="detail-val">${pdc.contact_operateur || 'Non renseigné'}</span></div>
                    </div>
                </div>

                <div class="detail-group">
                    <div class="detail-group-title">Caractéristiques techniques</div>
                    <div class="detail-grid-2">
                        <div class="detail-field"><span class="detail-key">Type de prise</span><span class="detail-val">${pdc.type_prise || 'Non renseigné'}</span></div>
                        <div class="detail-field"><span class="detail-key">Puissance</span><span class="detail-val">${pdc.puissance ? pdc.puissance + ' kW' : 'Non renseignée'}</span></div>
                        <div class="detail-field"><span class="detail-key">Câble T2 attaché</span><span class="detail-val">${pdc.cable_t2_attache ? 'Oui' : 'Non'}</span></div>
                    </div>
                </div>

                <div class="detail-group">
                    <div class="detail-group-title">Localisation</div>
                    <div class="detail-grid-2">
                        <div class="detail-field"><span class="detail-key">Latitude</span><span class="detail-val">${pdc.latitude || 'Non renseignée'}</span></div>
                        <div class="detail-field"><span class="detail-key">Longitude</span><span class="detail-val">${pdc.longitude || 'Non renseignée'}</span></div>
                        <div class="detail-field"><span class="detail-key">Commune</span><span class="detail-val">${pdc.commune || 'Non renseignée'}</span></div>
                        <div class="detail-field"><span class="detail-key">Département</span><span class="detail-val">${pdc.departement || 'Non renseigné'}</span></div>
                    </div>
                </div>

                <div class="detail-group">
                    <div class="detail-group-title">Paiement</div>
                    <div class="detail-grid-2">
                        <div class="detail-field"><span class="detail-key">Tarification</span><span class="detail-val">${pdc.tarification || 'Non renseignée'}</span></div>
                        <div class="detail-field"><span class="detail-key">Types de paiement</span><span class="detail-val">${pdc.types_paiement || 'Aucun moyen spécifié'}</span></div>
                        <div class="detail-field"><span class="detail-key">Gratuit</span><span class="detail-val">${pdc.gratuit ? 'Oui' : 'Non'}</span></div>
                    </div>
                </div>
            `;
        }

    } catch (error) {
        console.error('Erreur:', error);
    }
} 

if (document.querySelector('.detail-card')) {
    const params = new URLSearchParams(window.location.search);
    const paramId = params.get('id_pdc');
    const paramType = params.get('type_prise');

    if (paramId && paramType) {
        requestPDC(paramId, paramType);
    }
}
