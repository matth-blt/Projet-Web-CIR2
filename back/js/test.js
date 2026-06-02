'use strict';

async function requestStations() {
    try {
        const response = await fetch('../../utils/request.php/stations');
        const stations = await response.json();
        console.log(stations);
    } catch (error) {
        console.error('Erreur:', error);
        return
    }
}

requestStations()