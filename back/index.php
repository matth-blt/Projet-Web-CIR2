<?php
    require_once __DIR__ . '/auth.php';
    require_once __DIR__ . '/../api/Database.php';
    require_once __DIR__ . '/../api/models/PointDeCharge.php';

    $page_active = 'accueil';

    $db = Database::getConnection();
    $pdcModel = new PointDeCharge($db);
    $limit = 100;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    $total = $pdcModel->count();
    $pdcs = $pdcModel->getAll(false, $limit, $offset);

    $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

    include './php/header.php';
?>

<div class="content">
    <div class="page-header">
        <div>
        <div class="page-title">Administration des IRVE</div>
        <div class="page-sub">Affichage 100 par 100 — <?= $total ?> résultats</div>
        </div>
        <a href="php/create.php"><button class="btn-add">+ Ajouter</button></a>
    </div>

    <div class="table-wrap">
        <table>
        <thead>
            <tr>
            <th>Station</th>
            <th>Aménageur</th>
            <th>Opérateur</th>
            <th>Type de prise</th>
            <th>Commune</th>
            <th>Tarif</th>
            <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pdcs)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text3)">Aucun résultat</td></tr>
            <?php else: ?>
            <?php $i = 0; foreach ($pdcs as $pdc): $i++; ?>
                <tr style="animation-delay: <?= min($i * 0.05, 1) ?>s">
                <td data-label="Station"><?= htmlspecialchars($pdc['nom_station'] ?? '') ?></td>
                <td data-label="Aménageur"><?= htmlspecialchars($pdc['amenageur'] ?? '') ?></td>
                <td data-label="Opérateur"><?= htmlspecialchars($pdc['operateur'] ?? '') ?></td>
                <td data-label="Type de prise"><?= htmlspecialchars($pdc['type_prise'] ?? '') ?></td>
                <td data-label="Commune"><?= htmlspecialchars($pdc['commune'] ?? '') ?></td>
                <td data-label="Tarif"><?= htmlspecialchars($pdc['tarification'] ?? '') ?></td>
                <td>
                    <a href="php/detail.php?id_pdc=<?= urlencode($pdc['id_pdc']) ?>">
                    <button class="btn-view" title="Voir">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    </button>
                    </a>
                    <a href="php/edit.php?id_pdc=<?= urlencode($pdc['id_pdc']) ?>">
                    <button class="btn-edit" title="Modifier">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </button>
                    </a>
                    <form action="php/delete.php" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce point de charge ?');">
                        <input type="hidden" name="id_pdc" value="<?= htmlspecialchars($pdc['id_pdc']) ?>">
                        <input type="hidden" name="redirect" value="accueil">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn-delete" title="Supprimer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        </button>
                    </form>
                </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pager">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>"><button class="pager-btn">←</button></a>
        <?php else: ?>
            <button class="pager-btn" disabled>←</button>
        <?php endif; ?>

        <?php
            $range = [];
            if ($totalPages <= 7) {
                $range = range(1, $totalPages);
            } else {
                $range = [1];
                if ($page > 3) $range[] = '...';
                for ($p = max(2, $page - 1); $p <= min($totalPages - 1, $page + 1); $p++) {
                    $range[] = $p;
                }
                if ($page < $totalPages - 2) $range[] = '...';
                $range[] = $totalPages;
            }

            foreach ($range as $p):
            if ($p === '...'):
        ?>
            <span class="pager-dots">…</span>
        <?php else: ?>
            <a href="?page=<?= $p ?>">
            <button class="pager-btn <?= ($p === $page) ? 'active' : '' ?>"><?= $p ?></button>
            </a>
        <?php
            endif;
            endforeach;
        ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>"><button class="pager-btn">→</button></a>
        <?php else: ?>
            <button class="pager-btn" disabled>→</button>
        <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include './php/footer.php'; ?>
