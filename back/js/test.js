'use strict';

async function requestAmenageurs() {
    try {
        const response = await fetch('../../utils/request.php/amenageurs');
        const amenageurs = await response.json();
        console.log(amenageurs);
    } catch (error) {
        console.error('Erreur:', error);
        return
    }
}

requestAmenageurs()