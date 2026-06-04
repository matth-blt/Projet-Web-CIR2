<?php
$page_active = 'liste';

$id = $_GET['id'] ?? '';

include 'header.php';
?>

<div class="content">
  <a href="liste.php" class="back-link"><- Retour à la liste</a>

  <div class="detail-card">

    <!-- En-tête de la fiche -->
    <div class="detail-card-header">
      <!-- Rempli dynamiquement par request.js -> requestPDC() -->
    </div>

    <!-- Corps de la fiche -->
    <div class="detail-card-body">
      <!-- Rempli dynamiquement par request.js -> requestPDC() -->
    </div>

  </div>
</div>

<script src="../js/request_detail.js"></script>
<?php include 'footer.php'; ?>



