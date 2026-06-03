<?php
$page_active = 'liste';
include 'header.php';
?>

<div class="content">
  <a href="liste.php" class="back-link">&larr; Retour à la liste</a>

  <div class="edit-card">

    <!-- En-tête : nom de la station + ID, rempli par request_edit.js -->
    <div class="edit-card-header">
      <!-- Rempli dynamiquement par request_edit.js → requestPDC() -->
    </div>

    <!-- Corps : formulaire avec inputs pré-remplis, rempli par request_edit.js -->
    <div class="edit-card-body">
      <!-- Rempli dynamiquement par request_edit.js → requestPDC() -->
    </div>

  </div>
</div>

<script src="../js/request_edit.js"></script>
<?php include 'footer.php'; ?>
