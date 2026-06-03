<?php
require_once '../../api/database.php';

$page_active = 'liste';

$id_pdc = $_GET['id_pdc'] ?? '';
$type_prise = $_GET['type_prise'] ?? '';

$db  = dbConnect();
$res = ($db && $id_pdc && $type_prise) ? dbRequestPDC($db, $id_pdc, $type_prise) : [];
$pdc = $res[0] ?? null;

$success = false;
$error   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdc && $db) {
    $result = dbUpdatePDC($db, [
        'id_pdc' => $id_pdc,
        'puissance' => $_POST['puissance'] ?: null,
        'cable_t2_attache' => (int)($_POST['cable_t2_attache'] ?? 0),
        'latitude' => $_POST['latitude'] ?: null,
        'longitude' => $_POST['longitude'] ?: null,
        'tarification' => $_POST['tarification'] ?: null,
    ]);

    if ($result) {
        header('Location: detail.php?id_pdc=' . urlencode($id_pdc) . '&type_prise=' . urlencode($type_prise));
        exit;
    } else {
        $error = true;
    }
}

include 'header.php';
?>

<div class="content">
    <a href="liste.php" class="back-link">&larr; Retour à la liste</a>

    <?php if (!$pdc): ?>
        <p style="color:var(--text3)">Point de charge introuvable.</p>
    <?php else: ?>

    <?php if ($error): ?>
        <div class="info-box" style="border-color:#e24b4a;background:#fff0f0;color:#c0392b;margin-bottom:16px">
            Erreur lors de la sauvegarde. Veuillez réessayer.
        </div>
    <?php endif; ?>

    <div class="edit-card">
        <div class="edit-card-body">
        <form class="form-card" method="POST"
                action="edit.php?id_pdc=<?= urlencode($id_pdc) ?>&type_prise=<?= urlencode($type_prise) ?>">

            <!-- Lecture seule : données liées à la station -->
            <div class="form-section-title">Informations station</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nom station</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['nom_station'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">ID PDC</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['id_pdc'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Aménageur</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['amenageur'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">SIREN</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['siren_amenageur'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Opérateur</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['operateur'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Contact opérateur</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['contact_operateur'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Commune</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['commune'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Département</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['departement'] ?? '') ?>" disabled>
            </div>
            </div>

            <!-- Éditables : propres au point_de_charge -->
            <div class="form-section-title">Caractéristiques techniques</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Type de prise</label>
                <input class="form-input form-input--readonly" type="text" value="<?= htmlspecialchars($pdc['type_prise'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Puissance (kW)</label>
                <input class="form-input" type="number" name="puissance" value="<?= htmlspecialchars($pdc['puissance'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Câble T2 attaché</label>
                <select class="form-select" name="cable_t2_attache">
                <option value="1" <?= $pdc['cable_t2_attache'] ? 'selected' : '' ?>>Oui</option>
                <option value="0" <?= !$pdc['cable_t2_attache'] ? 'selected' : '' ?>>Non</option>
                </select>
            </div>
            </div>

            <div class="form-section-title">Localisation</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Latitude</label>
                <input class="form-input" type="text" name="latitude" value="<?= htmlspecialchars($pdc['latitude']  ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Longitude</label>
                <input class="form-input" type="text" name="longitude" value="<?= htmlspecialchars($pdc['longitude'] ?? '') ?>">
            </div>
            </div>

            <div class="form-section-title">Paiement</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Tarification</label>
                <input class="form-input" type="text" name="tarification" value="<?= htmlspecialchars($pdc['tarification'] ?? '') ?>">
            </div>
            </div>

            <div class="form-actions">
            <button type="submit" class="btn-save">Sauvegarder</button>
            <a href="detail.php?id_pdc=<?= urlencode($id_pdc) ?>&type_prise=<?= urlencode($type_prise) ?>">
                <button type="button" class="btn-cancel">Annuler</button>
            </a>
            </div>

        </form>
        </div>

    </div>

    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
