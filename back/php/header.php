<?php
// $page_active doit être défini avant l'inclusion : 'accueil', 'liste' ou 'nouveau'
$is_in_php_dir = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'php');
$php_dir = $is_in_php_dir ? '' : 'php/';
$back_dir = $is_in_php_dir ? '../' : '';
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IRVE Admin — Back-office</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= $back_dir ?>css/style.css" rel="stylesheet">
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
        <a href="<?= $back_dir ?>index.php" <?= ($page_active === 'accueil') ? 'class="active"' : '' ?>>Accueil</a>
        <a href="<?= $php_dir ?>liste.php" <?= ($page_active === 'liste')   ? 'class="active"' : '' ?>>Liste</a>
        <a href="<?= $php_dir ?>create.php" <?= ($page_active === 'nouveau') ? 'class="active"' : '' ?>>Nouveau</a>
    </nav>

    <button class="hamburger" id="hamburger" aria-label="Menu">
        <svg class="icon-menu" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path d="M3 5h18v2H3zm0 6h18v2H3zm0 6h18v2H3z"></path>
        </svg>
        <svg class="icon-close" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24" style="display:none">
        <path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/>
        </svg>
    </button>
</header>

<nav class="mobile-nav" id="mobile-nav">
    <a href="<?= $back_dir ?>index.php" <?= ($page_active === 'accueil') ? 'class="active"' : '' ?>>Accueil</a>
    <a href="<?= $php_dir ?>liste.php" <?= ($page_active === 'liste')   ? 'class="active"' : '' ?>>Liste</a>
    <a href="<?= $php_dir ?>create.php" <?= ($page_active === 'nouveau') ? 'class="active"' : '' ?>>Nouveau</a>
</nav>

<script>
    var hamburger  = document.getElementById('hamburger');
    var mobileNav = document.getElementById('mobile-nav');
    var iconMenu = hamburger.querySelector('.icon-menu');
    var iconClose = hamburger.querySelector('.icon-close');

    function openMenu() {
        mobileNav.classList.add('open');
        iconMenu.style.display = 'none';
        iconClose.style.display = 'block';
    }

    function closeMenu() {
        mobileNav.classList.remove('open');
        iconMenu.style.display = 'block';
        iconClose.style.display = 'none';
    }

    hamburger.addEventListener('click', function() {
        mobileNav.classList.contains('open') ? closeMenu() : openMenu();
    });

    mobileNav.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('click', function(e) {
        if (!hamburger.contains(e.target) && !mobileNav.contains(e.target)) {
        closeMenu();
        }
    });
</script>

