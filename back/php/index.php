<?php
require_once '../../utils/database.php';

$page_active = 'accueil';

$db = dbConnect();
$pdcs = $db ? dbRequestPDCS($db, true) : [];

include 'header.php';
?>

<div class="content">
    <div class="page-header">
        <div>
            <div class="page-title">Administration des IRVE</div>
            <div class="page-sub">Gestion des points de recharge véhicules électriques en Bretagne</div>
        </div>
        <a href="create.php"><button class="btn-add">+ Ajouter un point</button></a>
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
    </div>
</div>

<?php include 'footer.php'; ?>
