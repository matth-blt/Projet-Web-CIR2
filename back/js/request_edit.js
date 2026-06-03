'use strict';

async function requestPDC(id_pdc, type_prise) {
    try {
        const response = await fetch(`../../utils/request.php/pdcs?id_pdc=${id_pdc}&type_prise=${type_prise}`, {
            method: 'GET'
        });
        const pdcs = await response.json();
        const pdc = pdcs[0];

        if (!pdc) return;

        // --- En-tête ---
        // const header = document.querySelector('.edit-card-header');
        // if (header) {
        //     header.innerHTML = `
        //         <div>
        //             <h2>${pdc.nom_station || 'Non renseigné'}</h2>
        //             <p>ID PDC : ${pdc.id_pdc}</p>
        //         </div>
        //     `;
        // }

        // --- Corps : formulaire avec inputs pré-remplis ---
        const body = document.querySelector('.edit-card-body');
        if (body) {
            body.innerHTML = `
                <form class="form-card" id="edit-form">
                    <div class="form-section-title">Station</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Station <span class="required">*</span></label>
                            <input class="form-input" type="text" name="nom_station" value="${pdc.nom_station || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ID PDC</label>
                            <input class="form-input" type="text" name="id_pdc" value="${pdc.id_pdc || ''}">
                        </div>
                    </div>

                    <input type="hidden" name="id_pdc" value="${pdc.id_pdc}">
                    <input type="hidden" name="type_prise" value="${pdc.type_prise || ''}">

                    <div class="form-section-title">Aménageur &amp; Opérateur</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Aménageur <span class="required">*</span></label>
                            <input class="form-input" type="text" name="amenageur" value="${pdc.amenageur || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SIREN</label>
                            <input class="form-input" type="text" name="siren_amenageur" value="${pdc.siren_amenageur || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opérateur</label>
                            <input class="form-input" type="text" name="operateur" value="${pdc.operateur || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact opérateur</label>
                            <input class="form-input" type="text" name="contact_operateur" value="${pdc.contact_operateur || ''}">
                        </div>
                    </div>

                    <div class="form-section-title">Caractéristiques techniques</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Type de prise</label>
                            <input class="form-input" type="text" name="type_prise_edit" value="${pdc.type_prise || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Puissance (kW)</label>
                            <input class="form-input" type="number" name="puissance" value="${pdc.puissance || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Câble T2 attaché</label>
                            <select class="form-select" name="cable_t2_attache">
                                <option value="1" ${pdc.cable_t2_attache ? 'selected' : ''}>Oui</option>
                                <option value="0" ${!pdc.cable_t2_attache ? 'selected' : ''}>Non</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section-title">Localisation</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Commune</label>
                            <input class="form-input" type="text" name="commune" value="${pdc.commune || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Département</label>
                            <input class="form-input" type="text" name="departement" value="${pdc.departement || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input class="form-input" type="text" name="latitude" value="${pdc.latitude || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input class="form-input" type="text" name="longitude" value="${pdc.longitude || ''}">
                        </div>
                    </div>

                    <div class="form-section-title">Paiement</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Tarification</label>
                            <input class="form-input" type="text" name="tarification" value="${pdc.tarification || ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gratuit</label>
                            <select class="form-select" name="gratuit">
                                <option value="1" ${pdc.gratuit ? 'selected' : ''}>Oui</option>
                                <option value="0" ${!pdc.gratuit ? 'selected' : ''}>Non</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">Sauvegarder</button>
                        <a href="detail.php?id_pdc=${encodeURIComponent(pdc.id_pdc)}&type_prise=${encodeURIComponent(pdc.type_prise || '')}">
                            <button type="button" class="btn-cancel">Annuler</button>
                        </a>
                    </div>

                </form>
            `;

            // TODO : brancher le submit sur une requête POST/PUT vers l'API
        }

    } catch (error) {
        console.error('Erreur:', error);
    }
}

if (document.querySelector('.edit-card')) {
    const params = new URLSearchParams(window.location.search);
    const paramId   = params.get('id_pdc');
    const paramType = params.get('type_prise');

    if (paramId && paramType) {
        requestPDC(paramId, paramType);
    }
}
