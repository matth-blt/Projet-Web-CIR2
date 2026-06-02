<?php
// $page_active doit être défini avant l'inclusion : 'accueil', 'liste' ou 'nouveau'
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IRVE Admin — Back-office</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<header>
  <div class="logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 14H11v-5h2zm0-7H11V7h2z"/></svg>
    </div>
    <span class="logo-name">IRVE Admin</span>
    <span class="admin-badge">Back</span>
  </div>

  <nav>
    <a href="index.php" <?= ($page_active === 'accueil') ? 'class="active"' : '' ?>>Accueil</a>
    <a href="liste.php" <?= ($page_active === 'liste')   ? 'class="active"' : '' ?>>Liste</a>
    <a href="create.php" <?= ($page_active === 'nouveau') ? 'class="active"' : '' ?>>Nouveau</a>
  </nav>
</header>

