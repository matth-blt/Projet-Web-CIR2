<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../api/Database.php';
require_once __DIR__ . '/../../api/models/PointDeCharge.php';
require_once __DIR__ . '/../../api/models/Referentiel.php';

$page_active = 'nouveau';

$db = Database::getConnection();
$ref = new Referentiel($db);
$types_prises = $ref->getTypesPrise();
$types_paiement = $ref->getTypesPaiement();

$error = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdcModel = new PointDeCharge($db);
    $id_pdc = $pdcModel->create([
        // Station
        'nom_station' => $_POST['nom_station'] ?? '',
        'adresse_station' => $_POST['adresse_station'] ?? '',
        'id_station_itinerance' => $_POST['id_station_itinerance'] ?? '',
        'date_service' => $_POST['date_service'] ?? '',
        // Aménageur
        'nom_amenageur' => $_POST['nom_amenageur'] ?? '',
        'siren_amenageur' => (int)($_POST['siren_amenageur'] ?? 0),
        'contact_amenageur' => $_POST['contact_amenageur'] ?? '',
        // Opérateur
        'nom_operateur' => $_POST['nom_operateur'] ?? '',
        'contact_operateur' => $_POST['contact_operateur'] ?? '',
        'telephone_operateur' => $_POST['telephone_operateur'] ?? '',
        // Localisation
        'code_insee' => (int)($_POST['code_insee'] ?? 0),
        'nom_commune' => $_POST['nom_commune'] ?? '',
        'code_dep' => (int)($_POST['code_dep'] ?? 0),
        'nom_departement' => $_POST['nom_departement'] ?? '',
        'latitude' => $_POST['latitude'] ?? 0,
        'longitude' => $_POST['longitude'] ?? 0,
        // PDC
        'puissance' => $_POST['puissance'] ?? 0,
        'cable_t2_attache' => (int)($_POST['cable_t2_attache'] ?? 0),
        'gratuit' => (int)($_POST['gratuit'] ?? 0),
        'tarification' => $_POST['tarification'] ?? '',
        // Relations
        'type_prise' => $_POST['type_prise'] ?? '',
        'types_paiement' => $_POST['types_paiement'] ?? [],
    ]);

    if ($id_pdc !== false) {
        header('Location: detail.php?id_pdc=' . urlencode($id_pdc));
        exit;
    } else {
        $error = true;
        $message = 'Erreur lors de l\'insertion. Vérifie les données saisies.';
    }
}

include 'header.php';
?>

<div class="content">
    <a href="liste.php" class="back-link">&larr; Retour à la liste</a>

    <div class="page-header">
        <div>
        <div class="page-title">Nouveau point de recharge</div>
        <div class="page-sub">Tous les champs marqués <span class="required">*</span> sont obligatoires</div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="info-box" style="border-color:#e24b4a;background:#fff0f0;color:#c0392b;margin-bottom:16px">
        <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="edit-card">
        <div class="edit-card-body">
        <form class="form-card" method="POST" action="create.php">

            <!-- ── STATION ── -->
            <div class="form-section-title">Station</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nom de la station <span class="required">*</span></label>
                <input class="form-input" type="text" name="nom_station"
                    value="<?= htmlspecialchars($_POST['nom_station'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Adresse <span class="required">*</span></label>
                <input class="form-input" type="text" name="adresse_station" value="<?= htmlspecialchars($_POST['adresse_station'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Identifiant itinérance <small style="color:var(--text3)">(auto si vide)</small></label>
                <input class="form-input" type="text" name="id_station_itinerance"
                    placeholder="Ex : FRXXE1234"
                    value="<?= htmlspecialchars($_POST['id_station_itinerance'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Date de mise en service</label>
                <input class="form-input" type="date" name="date_service"
                    value="<?= htmlspecialchars($_POST['date_service'] ?? '') ?>">
            </div>
            </div>

            <!-- ── AMÉNAGEUR ── -->
            <div class="form-section-title">Aménageur</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nom <span class="required">*</span></label>
                <input class="form-input" type="text" name="nom_amenageur"
                    value="<?= htmlspecialchars($_POST['nom_amenageur'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">SIREN <span class="required">*</span></label>
                <input class="form-input" type="number" name="siren_amenageur"
                    placeholder="000000000"
                    value="<?= htmlspecialchars($_POST['siren_amenageur'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact</label>
                <input class="form-input" type="text" name="contact_amenageur"
                    placeholder="contact@amenageur.fr"
                    value="<?= htmlspecialchars($_POST['contact_amenageur'] ?? '') ?>">
            </div>
            </div>

            <!-- ── OPÉRATEUR ── -->
            <div class="form-section-title">Opérateur</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nom <span class="required">*</span></label>
                <input class="form-input" type="text" name="nom_operateur"
                    value="<?= htmlspecialchars($_POST['nom_operateur'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact</label>
                <input class="form-input" type="text" name="contact_operateur"
                    placeholder="contact@operateur.fr"
                    value="<?= htmlspecialchars($_POST['contact_operateur'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input class="form-input" type="tel" name="telephone_operateur"
                    placeholder="0368781435"
                    value="<?= htmlspecialchars($_POST['telephone_operateur'] ?? '') ?>">
            </div>
            </div>

            <!-- ── CARACTÉRISTIQUES TECHNIQUES ── -->
            <div class="form-section-title">Caractéristiques techniques</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Type de prise <span class="required">*</span></label>
                <select class="form-select" name="type_prise" required>
                <option value="">— Sélectionner —</option>
                <?php foreach ($types_prises as $tp): ?>
                    <option value="<?= htmlspecialchars($tp['type_prise']) ?>"
                    <?= (($_POST['type_prise'] ?? '') === $tp['type_prise']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tp['type_prise']) ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Puissance (kW) <span class="required">*</span></label>
                <input class="form-input" type="number" step="0.1" name="puissance"
                    value="<?= htmlspecialchars($_POST['puissance'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Câble T2 attaché</label>
                <select class="form-select" name="cable_t2_attache">
                <option value="0" <?= (($_POST['cable_t2_attache'] ?? '0') === '0') ? 'selected' : '' ?>>Non</option>
                <option value="1" <?= (($_POST['cable_t2_attache'] ?? '') === '1') ? 'selected' : '' ?>>Oui</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Gratuit</label>
                <select class="form-select" name="gratuit">
                <option value="0" <?= (($_POST['gratuit'] ?? '0') === '0') ? 'selected' : '' ?>>Non</option>
                <option value="1" <?= (($_POST['gratuit'] ?? '') === '1') ? 'selected' : '' ?>>Oui</option>
                </select>
            </div>
            </div>

            <!-- ── LOCALISATION ── -->
            <div class="form-section-title">Localisation</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Code INSEE commune <span class="required">*</span></label>
                <input class="form-input" type="number" name="code_insee"
                    placeholder="29019"
                    value="<?= htmlspecialchars($_POST['code_insee'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nom de la commune <span class="required">*</span></label>
                <input class="form-input" type="text" name="nom_commune"
                    placeholder="Brest"
                    value="<?= htmlspecialchars($_POST['nom_commune'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Code département <span class="required">*</span></label>
                <select class="form-select" name="code_dep" required>
                <option value="">— Sélectionner —</option>
                <?php foreach ([22 => 'Côtes-d\'Armor', 29 => 'Finistère', 35 => 'Ille-et-Vilaine', 56 => 'Morbihan'] as $code => $nom): ?>
                    <option value="<?= $code ?>"
                    <?= (($_POST['code_dep'] ?? '') == $code) ? 'selected' : '' ?>>
                    <?= $code ?> — <?= $nom ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nom du département <span class="required">*</span></label>
                <input class="form-input" type="text" name="nom_departement"
                    placeholder="Finistère"
                    value="<?= htmlspecialchars($_POST['nom_departement'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Latitude <span class="required">*</span></label>
                <input class="form-input" type="text" name="latitude"
                    placeholder="48.3904"
                    value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Longitude <span class="required">*</span></label>
                <input class="form-input" type="text" name="longitude"
                    placeholder="-4.4861"
                    value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>" required>
            </div>
            </div>

            <!-- ── PAIEMENT ── -->
            <div class="form-section-title">Paiement</div>
            <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Tarification</label>
                <input class="form-input" type="text" name="tarification"
                    placeholder="Ex : 0,30 €/kWh"
                    value="<?= htmlspecialchars($_POST['tarification'] ?? '') ?>">
            </div>
            <div class="form-group form-group--full">
                <label class="form-label">Types de paiement</label>
                <div class="toggle-group">
                <?php
                    $selected_pay = $_POST['types_paiement'] ?? [];
                    foreach ($types_paiement as $tp):
                    $val     = htmlspecialchars($tp['type_paiement']);
                    $checked = in_array($tp['type_paiement'], $selected_pay) ? 'checked' : '';
                ?>
                    <label class="toggle-btn">
                    <input type="checkbox" name="types_paiement[]" value="<?= $val ?>" <?= $checked ?> hidden>
                    <span><?= $val ?></span>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>
            </div>

            <div class="form-actions">
            <button type="submit" class="btn-save">Enregistrer</button>
            <a href="liste.php"><button type="button" class="btn-cancel">Annuler</button></a>
            </div>

        </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
