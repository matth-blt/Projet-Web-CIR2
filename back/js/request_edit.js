'use strict';

async function requestPDC(id_pdc, type_prise) {
    try {
        const response = await fetch(`../../utils/request.php/pdcs?id_pdc=${id_pdc}&type_prise=${type_prise}`, {
            method: 'GET'
        });
        const pdcs = await response.json();
        const pdc = pdcs[0];

        if (!pdc) return;

        const body = document.querySelector('.edit-card-body');
        if (body) {
            body.innerHTML = `
                <form class="form-card" id="edit-form">

                    <input type="hidden" name="id_pdc" value="${pdc.id_pdc}">

                    <div class="form-section-title">Informations station</span></div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nom station</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.nom_station || ''}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Id du point de charge</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.id_pdc || ''}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Aménageur</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.amenageur || ''}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SIREN</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.siren_amenageur || ''}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opérateur</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.operateur || ''}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact opérateur</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.contact_operateur || ''}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Commune</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.commune || ''}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Département</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.departement || ''}" disabled>
                        </div>
                    </div>

                    <!-- Champs éditables : propres au point_de_charge -->
                    <div class="form-section-title">Caractéristiques techniques</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Type de prise</label>
                            <input class="form-input form-input--readonly" type="text" value="${pdc.type_prise || ''}" disabled>
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
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">Sauvegarder</button>
                        <a href="detail.php?id_pdc=${encodeURIComponent(pdc.id_pdc)}&type_prise=${encodeURIComponent(pdc.type_prise || '')}">
                            <button type="button" class="btn-cancel">Annuler</button>
                        </a>
                    </div>

                </form>
            `;

            document.getElementById('edit-form').addEventListener('submit', async (e) => {
                e.preventDefault();

                const fd = new FormData(e.target);

                const payload = {
                    puissance: fd.get('puissance') || null,
                    cable_t2_attache: parseInt(fd.get('cable_t2_attache'), 10),
                    latitude: fd.get('latitude') || null,
                    longitude: fd.get('longitude') || null,
                    tarification: fd.get('tarification') || null,
                };

                try {
                    const res = await fetch(`../../utils/request.php/pdcs/${encodeURIComponent(pdc.id_pdc)}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload),
                        }
                    );

                    if (res.ok) {
                        window.location.href = `detail.php?id_pdc=${encodeURIComponent(pdc.id_pdc)}&type_prise=${encodeURIComponent(pdc.type_prise || '')}`;
                    } else {
                        console.error('Erreur lors de la sauvegarde :', res.status);
                        alert('Erreur lors de la sauvegarde. Vérifie la console.');
                    }
                } catch (err) {
                    console.error('Erreur réseau :', err);
                    alert('Erreur réseau. Vérifie la console.');
                }
            });
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
