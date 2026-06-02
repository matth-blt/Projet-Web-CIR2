<?php
$page_active = 'accueil';
include 'header.php';

$installations = [
  ['id' => 'FRXXE7400123', 'amenageur' => 'R3', 'prise' => 'Combo CCS', 'commune' => 'Brest',    'service' => '03/2023'],
  ['id' => 'FRXXE7400456', 'amenageur' => 'ELECTRIC 55 CHARGING','prise' => 'Type 2',    'commune' => 'Rennes',   'service' => '07/2022'],
  ['id' => 'FRXXE7400789', 'amenageur' => 'R3', 'prise' => 'CHAdeMO',   'commune' => 'Vannes',   'service' => '11/2024'],
  ['id' => 'FRXXE7400812', 'amenageur' => 'LE ROUX LOISIRS', 'prise' => 'Type 2',    'commune' => 'Lorient',  'service' => '01/2023'],
];
?>

<div class="content">
  <div class="page-header">
    <div>
      <div class="page-title">Administration des IRVE</div>
      <div class="page-sub">Gestion des points de recharge véhicules électriques en Bretagne</div>
    </div>
    <a href="create.php"><button class="btn-add">+ Ajouter un point</button></a>
  </div>

  <!-- <div class="info-box">
    Interface d'administration permettant de consulter, créer et modifier les installations IRVE.
    Toutes les opérations passent par l'API REST (PDO/PHP) et sont stockées en MariaDB.
  </div> -->

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID Installation</th>
          <th>Aménageur</th>
          <th>Opérateur</th>
          <th>Type de prise</th>
          <th>Commune</th>
          <th>Tarif</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="stations-table-body">
        <!-- Le contenu sera injecté par test.js -->
      </tbody>
    </table>
  </div>
</div>
<script src="../js/test.js"></script>

<?php include 'footer.php'; ?>

