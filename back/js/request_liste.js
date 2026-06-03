'use strict';

const LIMIT = 100;
let currentPage = 1;
let totalPages  = 1;

async function requestPDCS(page = 1) {
    try {
        const response = await fetch(`../../utils/request.php/pdcs?page=${page}&limit=${LIMIT}`);

        const total = parseInt(response.headers.get('X-Total-Count') || '0', 10);
        totalPages = Math.ceil(total / LIMIT);

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

        renderPager();

    } catch (error) {
        console.error('Erreur:', error);
    }
}

function renderPager() {
    const pager = document.getElementById('pager');
    if (!pager) return;

    pager.innerHTML = '';

    const prev = document.createElement('button');
    prev.className = 'pager-btn';
    prev.textContent = '←';
    prev.disabled = currentPage === 1;
    prev.addEventListener('click', () => goToPage(currentPage - 1));
    pager.appendChild(prev);

    const range = getPageRange(currentPage, totalPages);
    range.forEach(p => {
        if (p === '...') {
            const dots = document.createElement('span');
            dots.className = 'pager-dots';
            dots.textContent = '…';
            pager.appendChild(dots);
        } else {
            const btn = document.createElement('button');
            btn.className = 'pager-btn' + (p === currentPage ? ' active' : '');
            btn.textContent = p;
            btn.addEventListener('click', () => goToPage(p));
            pager.appendChild(btn);
        }
    });

    const next = document.createElement('button');
    next.className = 'pager-btn';
    next.textContent = '→';
    next.disabled = currentPage === totalPages;
    next.addEventListener('click', () => goToPage(currentPage + 1));
    pager.appendChild(next);
}

function goToPage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    requestPDCS(currentPage);
    // Remonter en haut du tableau
    document.querySelector('.table-wrap')?.scrollIntoView({ behavior: 'smooth' });
}

function getPageRange(current, total) {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    const pages = [1];
    if (current > 3) pages.push('...');
    for (let p = Math.max(2, current - 1); p <= Math.min(total - 1, current + 1); p++) {
        pages.push(p);
    }
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
}

if (document.getElementById('pdcs-table-body')) {
    requestPDCS(currentPage);
}
