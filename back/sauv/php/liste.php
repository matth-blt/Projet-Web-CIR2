<?php
// PAGE 2 â€” Liste complÃ¨te des installations (avec pagination)

$page_active = 'liste';
include 'header.php';
?>

<div class="content">
  <div class="page-header">
    <div>
      <div class="page-title">Tous les Points de Charge</div>
      <div class="page-sub">Affichage 100 par 100</div>
    </div>
    <a href="/php/create.php"><button class="btn-add">+ Ajouter</button></a>
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

    <!-- Pagination générée dynamiquement par request_liste.js -->
    <div class="pager" id="pager"></div>
  </div>
</div>
<script src="../js/request_liste.js"></script>
<?php include 'footer.php'; ?>

