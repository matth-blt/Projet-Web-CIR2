<?php
require_once '../../api/Database.php';
require_once '../../api/models/PointDeCharge.php';

$page_active = 'liste';

$db = Database::getConnection();
$pdcModel = new PointDeCharge($db);
$limit = 100;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total = $pdcModel->count();
$pdcs = $pdcModel->getAll(false, $limit, $offset);

$totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

include 'header.php';
?>

<div class="content">
    <div class="page-header">
        <div>
        <div class="page-title">Tous les Points de Charge</div>
        <div class="page-sub">Affichage 100 par 100 — <?= $total ?> résultats</div>
        </div>
        <a href="create.php"><button class="btn-add">+ Ajouter</button></a>
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
                <td><?= htmlspecialchars($pdc['nom_station'] ?? '') ?></td>
                <td><?= htmlspecialchars($pdc['amenageur'] ?? '') ?></td>
                <td><?= htmlspecialchars($pdc['operateur'] ?? '') ?></td>
                <td><?= htmlspecialchars($pdc['type_prise'] ?? '') ?></td>
                <td><?= htmlspecialchars($pdc['commune'] ?? '') ?></td>
                <td><?= htmlspecialchars($pdc['tarification'] ?? '') ?></td>
                <td>
                    <a href="detail.php?id_pdc=<?= urlencode($pdc['id_pdc']) ?>&type_prise=<?= urlencode($pdc['type_prise'] ?? '') ?>">
                    <button class="btn-view">Voir</button>
                    </a>
                    <a href="edit.php?id_pdc=<?= urlencode($pdc['id_pdc']) ?>&type_prise=<?= urlencode($pdc['type_prise'] ?? '') ?>">
                    <button class="btn-edit">Modifier</button>
                    </a>
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

<?php include 'footer.php'; ?>
