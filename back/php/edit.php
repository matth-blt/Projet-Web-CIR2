<?php
// PAGE 4 â€” Formulaire de modification d'un point de recharge existant
// L'ID est passÃ© en paramÃ¨tre GET : /back/edit.php?id=FRXXE7400123

$page_active = 'liste';

// RÃ©cupÃ¨re l'ID depuis l'URL (ex: ?id=FRXXE7400123)
$id = $_GET['id'] ?? '';

// TODO : remplacer par une vraie requÃªte SQL  â†’  SELECT * FROM irve WHERE id = ?
// DonnÃ©es d'exemple prÃ©-remplies
$installation = [
  'id' => 'FRXXE7400123',
  'amenageur' => 'R3',
  'siren' => '902 726 488',
  'operateur' => 'SPV COM',
  'contact' => 'exploitation@r3-charge.fr',
  'type_prise' => 'Combo CCS',
  'puissance' => '22',
  'nb_points' => '2',
  'date_service'=> '2023-03-15',
  'commune' => 'Brest',
  'dept' => '29',
  'latitude' => '48.3904',
  'longitude' => '-4.4861',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO : valider puis mettre Ã  jour en base via PDO
    header('Location: /back/detail.php?id=' . urlencode($id));
    exit;
}

include 'header.php';
?>

<div class="content">
  <a href="/php/liste.php" class="back-link">â† Retour Ã  la liste</a>

  <div class="page-header">
    <div>
      <div class="page-title">Modifier le point de recharge</div>
      <div class="page-sub">ID : <?= htmlspecialchars($installation['id']) ?> Â· /back/edit/{id}</div>
    </div>
  </div>

  <form class="form-card" method="POST" action="/php/edit.php?id=<?= urlencode($id) ?>">

    <div class="form-section-title">Informations amÃ©nageur</div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Aménageur <span class="required">*</span></label>
        <input class="form-input" type="text" name="amenageur" value="<?= htmlspecialchars($installation['amenageur']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">SIREN</label>
        <input class="form-input" type="text" name="siren" value="<?= htmlspecialchars($installation['siren']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Opérateur</label>
        <input class="form-input" type="text" name="operateur" value="<?= htmlspecialchars($installation['operateur']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Contact</label>
        <input class="form-input" type="email" name="contact" value="<?= htmlspecialchars($installation['contact']) ?>">
      </div>
    </div>

    <div class="form-section-title">Caractéristiques techniques</div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Type de prise</label>
        <select class="form-select" name="type_prise">
          <?php foreach (['Combo CCS', 'CHAdeMO', 'Type 2'] as $option): ?>
            <option value="<?= $option ?>" <?= ($installation['type_prise'] === $option) ? 'selected' : '' ?>>
              <?= $option ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Puissance (kW)</label>
        <input class="form-input" type="number" name="puissance" value="<?= htmlspecialchars($installation['puissance']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Nb points</label>
        <input class="form-input" type="number" name="nb_points" value="<?= htmlspecialchars($installation['nb_points']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Mise en service</label>
        <input class="form-input" type="date" name="date_service" value="<?= htmlspecialchars($installation['date_service']) ?>">
      </div>
    </div>

    <div class="form-section-title">Localisation</div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Commune</label>
        <input class="form-input" type="text" name="commune" value="<?= htmlspecialchars($installation['commune']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Département</label>
        <select class="form-select" name="dept">
          <?php foreach (['22', '29', '35', '56'] as $d): ?>
            <option value="<?= $d ?>" <?= ($installation['dept'] === $d) ? 'selected' : '' ?>>
              <?= $d ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Latitude</label>
        <input class="form-input" type="text" name="latitude" value="<?= htmlspecialchars($installation['latitude']) ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Longitude</label>
        <input class="form-input" type="text" name="longitude" value="<?= htmlspecialchars($installation['longitude']) ?>">
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-save">Sauvegarder</button>
      <a href="back/php/liste.php"><button type="button" class="btn-cancel">Annuler</button></a>
    </div>

  </form>
</div>

<?php include 'footer.php'; ?>
