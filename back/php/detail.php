<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../api/Database.php';
require_once __DIR__ . '/../../api/models/PointDeCharge.php';

$page_active = 'liste';

$id_pdc = $_GET['id_pdc'] ?? '';

$db = Database::getConnection();
$pdcModel = new PointDeCharge($db);
$pdc = $id_pdc ? $pdcModel->getById((int)$id_pdc) : null;

include 'header.php';
?>

<div class="content">
    <a href="liste.php" class="back-link">&larr; Retour à la liste</a>

    <?php if (!$pdc): ?>
        <p style="color:var(--text3)">Point de charge introuvable.</p>
    <?php else: ?>

    <div class="detail-card">

        <div class="detail-card-header">
        <div>
            <h2><?= htmlspecialchars($pdc['nom_station'] ?? 'Non renseigné') ?></h2>
            <p>ID PDC : <?= htmlspecialchars($pdc['id_pdc']) ?></p>
        </div>
        <a href="edit.php?id_pdc=<?= urlencode($pdc['id_pdc']) ?>">
            <button class="btn-edit">Modifier</button>
        </a>
        </div>

        <div class="detail-card-body">

        <div class="detail-group">
            <div class="detail-group-title">Aménageur &amp; Opérateur</div>
            <div class="detail-grid-2">
            <div class="detail-field"><span class="detail-key">Aménageur</span><span class="detail-val"><?= htmlspecialchars($pdc['amenageur'] ?? 'Non renseigné') ?></span></div>
            <div class="detail-field"><span class="detail-key">SIREN</span><span class="detail-val"><?= htmlspecialchars($pdc['siren_amenageur'] ?? 'Non renseigné') ?></span></div>
            <div class="detail-field"><span class="detail-key">Opérateur</span><span class="detail-val"><?= htmlspecialchars($pdc['operateur'] ?? 'Non renseigné') ?></span></div>
            <div class="detail-field"><span class="detail-key">Contact</span><span class="detail-val"><?= htmlspecialchars($pdc['contact_operateur'] ?? 'Non renseigné') ?></span></div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-group-title">Caractéristiques techniques</div>
            <div class="detail-grid-2">
            <div class="detail-field"><span class="detail-key">Type de prise</span><span class="detail-val"><?= htmlspecialchars($pdc['type_prise'] ?? 'Non renseigné') ?></span></div>
            <div class="detail-field"><span class="detail-key">Puissance</span><span class="detail-val"><?= $pdc['puissance'] ? htmlspecialchars($pdc['puissance']) . ' kW' : 'Non renseignée' ?></span></div>
            <div class="detail-field"><span class="detail-key">Câble T2 attaché</span><span class="detail-val"><?= $pdc['cable_t2_attache'] ? 'Oui' : 'Non' ?></span></div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-group-title">Localisation</div>
            <div class="detail-grid-2">
            <div class="detail-field"><span class="detail-key">Latitude</span><span class="detail-val"><?= htmlspecialchars($pdc['latitude'] ?? 'Non renseignée') ?></span></div>
            <div class="detail-field"><span class="detail-key">Longitude</span><span class="detail-val"><?= htmlspecialchars($pdc['longitude'] ?? 'Non renseignée') ?></span></div>
            <div class="detail-field"><span class="detail-key">Commune</span><span class="detail-val"><?= htmlspecialchars($pdc['commune'] ?? 'Non renseignée') ?></span></div>
            <div class="detail-field"><span class="detail-key">Département</span><span class="detail-val"><?= htmlspecialchars($pdc['departement'] ?? 'Non renseigné') ?></span></div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-group-title">Paiement</div>
            <div class="detail-grid-2">
            <div class="detail-field"><span class="detail-key">Tarification</span><span class="detail-val"><?= htmlspecialchars($pdc['tarification'] ?? 'Non renseignée') ?></span></div>
            <div class="detail-field"><span class="detail-key">Types de paiement</span><span class="detail-val"><?= htmlspecialchars($pdc['types_paiement'] ?? 'Aucun moyen spécifié') ?></span></div>
            <div class="detail-field"><span class="detail-key">Gratuit</span><span class="detail-val"><?= ($pdc['gratuit'] ?? null) ? 'Oui' : 'Non' ?></span></div>
            </div>
        </div>

        </div>
    </div>

    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
