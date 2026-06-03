<?php
$page_active = 'accueil';
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
      <tbody id="pdcs-table-body">
        <!-- contenu injecté par request.js -->
      </tbody>
    </table>
  </div>
</div>
<script src="../js/request_index.js"></script>
<?php include 'footer.php'; ?>

