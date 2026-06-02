<?php
// PAGE 2 â€” Liste complÃ¨te des installations (avec pagination)

$page_active = 'liste';
include 'header.php';

// DonnÃ©es d'exemple (Ã  remplacer par une vraie requÃªte SQL avec LIMIT/OFFSET)
$installations = [
  ['id' => 'FRXXE7400123', 'amenageur' => 'R3', 'prise' => 'Combo CCS', 'puissance' => '22 kW',  'commune' => 'Brest',   'dept' => '29', 'service' => '03/2023'],
  ['id' => 'FRXXE7400456', 'amenageur' => 'ELECTRIC 55', 'prise' => 'Type 2',    'puissance' => '50 kW',  'commune' => 'Rennes',  'dept' => '35', 'service' => '07/2022'],
  ['id' => 'FRXXE7400789', 'amenageur' => 'R3', 'prise' => 'CHAdeMO',   'puissance' => '150 kW', 'commune' => 'Vannes',  'dept' => '56', 'service' => '11/2024'],
  ['id' => 'FRXXE7400812', 'amenageur' => 'LE ROUX LOISIRS', 'prise' => 'Type 2',    'puissance' => '22 kW',  'commune' => 'Lorient', 'dept' => '56', 'service' => '01/2023'],
  ['id' => 'FRXXE7400999', 'amenageur' => 'Freshmile', 'prise' => 'Combo CCS', 'puissance' => '100 kW', 'commune' => 'Quimper', 'dept' => '29', 'service' => '05/2024'],
];
?>

<div class="content">
  <div class="page-header">
    <div>
      <div class="page-title">Toutes les installations</div>
      <div class="page-sub">Affichage limité à 100 enregistrements Â· /back/liste</div>
    </div>
    <a href="/php/create.php"><button class="btn-add">+ Ajouter</button></a>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Aménageur</th>
          <th>Type prise</th>
          <th>Puissance</th>
          <th>Commune</th>
          <th>Dépt.</th>
          <th>Service</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($installations as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['id']) ?></td>
          <td><?= htmlspecialchars($row['amenageur']) ?></td>
          <td><?= htmlspecialchars($row['prise']) ?></td>
          <td><?= htmlspecialchars($row['puissance']) ?></td>
          <td><?= htmlspecialchars($row['commune']) ?></td>
          <td><?= htmlspecialchars($row['dept']) ?></td>
          <td><?= htmlspecialchars($row['service']) ?></td>
          <td>
            <a href="detail.php?id=<?= urlencode($row['id']) ?>"><button class="btn-view">Voir</button></a>
            <a href="edit.php?id=<?= urlencode($row['id']) ?>"><button class="btn-edit">Ã‰diter</button></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <div class="pager">
      <button class="pager-btn active">1</button>
      <button class="pager-btn">2</button>
      <button class="pager-btn">3</button>
      <button class="pager-btn">â†’</button>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>



