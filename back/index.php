<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/Database.php';
require_once __DIR__ . '/../api/models/PointDeCharge.php';

$page_active = 'accueil';

$db = Database::getConnection();
$pdcModel = new PointDeCharge($db);
$pdcs = $pdcModel->getAll(accueil: true);

include './php/header.php';
?>

<div class="content">
    <div class="page-header">
        <div>
            <div class="page-title">Administration des IRVE</div>
            <div class="page-sub">Gestion des points de recharge véhicules électriques en Bretagne</div>
        </div>
        <a href="php/create.php"><button class="btn-add">+ Ajouter un point</button></a>
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
                    <button class="btn-view">Voir</button>
                    </a>
                    <a href="php/edit.php?id_pdc=<?= urlencode($pdc['id_pdc']) ?>">
                    <button class="btn-edit">Modifier</button>
                    </a>
                    <form action="php/delete.php" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce point de charge ?');">
                        <input type="hidden" name="id_pdc" value="<?= htmlspecialchars($pdc['id_pdc']) ?>">
                        <input type="hidden" name="redirect" value="accueil">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn-delete">Supprimer</button>
                    </form>
                </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

<?php include 'php/footer.php'; ?>
