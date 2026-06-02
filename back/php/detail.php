<?php
// PAGE 5 â€” DÃ©tail d'un point de recharge
// L'ID est passÃ© en paramÃ¨tre GET : /back/detail.php?id=FRXXE7400123

$page_active = 'liste';

$id = $_GET['id'] ?? '';

// TODO : remplacer par une vraie requÃªte SQL  â†’  SELECT * FROM irve WHERE id = ?
// DonnÃ©es d'exemple
$installation = [
  'id'          => 'FRXXE7400123',
  'nom_station' => 'Station Brest <-” Rue de l\'Alma',
  'amenageur'   => 'R3',
  'siren'       => '902 726 488',
  'operateur'   => 'SPV COM',
  'contact'     => 'exploitation@r3-charge.fr',
  'type_prise'  => 'Combo CCS',
  'puissance'   => '22 kW',
  'nb_points'   => '2',
  'date_service'=> '03 / 2023',
  'commune'     => 'Brest',
  'dept'        => '29 <-” Finistaire',
  'latitude'    => '48.3904Â° N',
  'longitude'   => '4.4861Â° W',
];

include 'header.php';
?>

<div class="content">
  <a href="/php/liste.php" class="back-link"><- Retour à la liste</a>

  <div class="detail-card">

    <!-- En-tête de la fiche -->
    <div class="detail-card-header">
      <div>
        <h2><?= htmlspecialchars($installation['nom_station']) ?></h2>
        <p>ID : <?= htmlspecialchars($installation['id']) ?> Â· /back/detail/<?= htmlspecialchars($installation['id']) ?></p>
      </div>
      <a href="/php/edit.php?id=<?= urlencode($id) ?>"><button class="btn-edit">Modifier</button></a>
    </div>

    <!-- Corps de la fiche -->
    <div class="detail-card-body">

      <div class="detail-group">
        <div class="detail-group-title">Aménageur  Opérateur</div>
        <div class="detail-grid-2">
          <div class="detail-field"><span class="detail-key">Aménageur</span><span class="detail-val"><?= htmlspecialchars($installation['amenageur']) ?></span></div>
          <div class="detail-field"><span class="detail-key">SIREN</span><span class="detail-val"><?= htmlspecialchars($installation['siren']) ?></span></div>
          <div class="detail-field"><span class="detail-key">Opérateur</span><span class="detail-val"><?= htmlspecialchars($installation['operateur']) ?></span></div>
          <div class="detail-field"><span class="detail-key">Contact</span><span class="detail-val"><?= htmlspecialchars($installation['contact']) ?></span></div>
        </div>
      </div>

      <div class="detail-group">
        <div class="detail-group-title">Caractéristiques techniques</div>
        <div class="detail-grid-2">
          <div class="detail-field"><span class="detail-key">Type de prise</span><span class="detail-val"><?= htmlspecialchars($installation['type_prise']) ?></span></div>
          <div class="detail-field"><span class="detail-key">Puissance</span><span class="detail-val"><?= htmlspecialchars($installation['puissance']) ?></span></div>
          <div class="detail-field"><span class="detail-key">Nb points</span><span class="detail-val"><?= htmlspecialchars($installation['nb_points']) ?></span></div>
          <div class="detail-field"><span class="detail-key">Mise en service</span><span class="detail-val"><?= htmlspecialchars($installation['date_service']) ?></span></div>
        </div>
      </div>

      <div class="detail-group">
        <div class="detail-group-title">Localisation</div>
        <div class="detail-grid-2">
          <div class="detail-field"><span class="detail-key">Commune</span><span class="detail-val"><?= htmlspecialchars($installation['commune']) ?></span></div>
          <div class="detail-field"><span class="detail-key">DÃ©partement</span><span class="detail-val"><?= htmlspecialchars($installation['dept']) ?></span></div>
          <div class="detail-field"><span class="detail-key">Latitude</span><span class="detail-val"><?= htmlspecialchars($installation['latitude']) ?></span></div>
          <div class="detail-field"><span class="detail-key">Longitude</span><span class="detail-val"><?= htmlspecialchars($installation['longitude']) ?></span></div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include 'footer.php'; ?>



