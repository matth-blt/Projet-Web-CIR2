<?php
// PAGE 3 â€” Formulaire de crÃ©ation d'un nouveau point de recharge
// Quand le formulaire est soumis (mÃ©thode POST), on traite les donnÃ©es ici.

$page_active = 'nouveau';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO : valider puis insÃ©rer en base via PDO
    // Exemple : $amenageur = $_POST['amenageur'];
    header('Location: /back/liste.php');
    exit;
}

include 'header.php';
?>

<div class="content">
  <div class="page-header">
    <div>
      <div class="page-title">Nouveau point de recharge</div>
      <div class="page-sub">/back/create</div>
    </div>
  </div>

  <form class="form-card" method="POST" action="/php/create.php">

    <div class="form-section-title">Informations amÃ©nageur</div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">AmÃ©nageur <span class="required">*</span></label>
        <input class="form-input" type="text" name="amenageur" placeholder="Ex : R3" required>
      </div>
      <div class="form-group">
        <label class="form-label">SIREN</label>
        <input class="form-input" type="text" name="siren" placeholder="000 000 000">
      </div>
      <div class="form-group">
        <label class="form-label">OpÃ©rateur <span class="required">*</span></label>
        <input class="form-input" type="text" name="operateur" placeholder="Ex : SPV COM" required>
      </div>
      <div class="form-group">
        <label class="form-label">Contact opÃ©rateur</label>
        <input class="form-input" type="email" name="contact" placeholder="contact@operateur.fr">
      </div>
    </div>

    <div class="form-section-title">CaractÃ©ristiques techniques</div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Type de prise <span class="required">*</span></label>
        <select class="form-select" name="type_prise" required>
          <option value="">â€” SÃ©lectionner â€”</option>
          <option value="Combo CCS">Combo CCS</option>
          <option value="CHAdeMO">CHAdeMO</option>
          <option value="Type 2">Type 2</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Puissance (kW) <span class="required">*</span></label>
        <input class="form-input" type="number" name="puissance" placeholder="22" required>
      </div>
      <div class="form-group">
        <label class="form-label">Nb points de charge</label>
        <input class="form-input" type="number" name="nb_points" placeholder="1">
      </div>
      <div class="form-group">
        <label class="form-label">Mise en service</label>
        <input class="form-input" type="date" name="date_service">
      </div>
    </div>

    <div class="form-section-title">Localisation</div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">Commune <span class="required">*</span></label>
        <input class="form-input" type="text" name="commune" placeholder="Ex : Brest" required>
      </div>
      <div class="form-group">
        <label class="form-label">DÃ©partement <span class="required">*</span></label>
        <select class="form-select" name="dept" required>
          <option value="">â€” SÃ©lectionner â€”</option>
          <option value="22">22</option>
          <option value="29">29</option>
          <option value="35">35</option>
          <option value="56">56</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Latitude</label>
        <input class="form-input" type="text" name="latitude" placeholder="48.0000">
      </div>
      <div class="form-group">
        <label class="form-label">Longitude</label>
        <input class="form-input" type="text" name="longitude" placeholder="-4.0000">
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-save">Enregistrer</button>
      <a href="/php/liste.php"><button type="button" class="btn-cancel">Annuler</button></a>
    </div>

  </form>
</div>

<?php include 'footer.php'; ?>



