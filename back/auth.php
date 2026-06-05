<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$USERS = [
    'matthieu' => '$2y$12$wshtRls.2HJZ4CeSF/qBnuvhKOoFaoHQ/3mnAxBeEa2884dXKSAEG',
    'noah' => '$2y$12$TIBgf0gDMR1zeIxkQNEAUO40JSPxvwx1YTIyWxf.kQ6e9O1fUGMES'
];

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
?><!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <script>
            localStorage.removeItem('auth_user');
            window.location.href = '<?= strtok($_SERVER["REQUEST_URI"], "?") ?>';
        </script>
    </head>
    <body>
    </body>
    </html>
    <?php
    exit;
}

$erreur = false;
if (isset($_POST['login_user'], $_POST['login_pass'])) {
    $u = $_POST['login_user'];
    $p = $_POST['login_pass'];
    if (isset($USERS[$u]) && password_verify($p, $USERS[$u])) {
        $_SESSION['auth_user'] = $u;
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <script>
                localStorage.setItem('auth_user', <?= json_encode($u) ?>);
                window.location.href = '<?= $_SERVER['REQUEST_URI'] ?>';
            </script>
        </head>
        <body>
        </body>
        </html>
        <?php
        exit;
    }
    $erreur = true;
}

if (!empty($_SESSION['auth_user'])) {
    return;
}

$base = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'php') ? '../' : '';

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — IRVE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= $base ?>css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="split">
    <div class="split-art">
      <img src="<?= $base ?>assets/img.png" alt="Borne de recharge IRVE">
    </div>

    <div class="split-form">
        <form class="form-inner" method="POST" action="">
            <div class="login-title">Connexion</div>
            <div class="login-sub">IRVE Admin — Back</div>

        <?php if ($erreur): ?>
            <div class="login-err">Identifiant ou mot de passe incorrect.</div>
        <?php endif; ?>

        <div class="field">
            <span class="field-ico"><svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/></svg></span>
            <input type="text" name="login_user" placeholder="Identifiant" autofocus required value="<?= htmlspecialchars($_POST['login_user'] ?? '') ?>">
        </div>
        <div class="field">
            <span class="field-ico"><svg viewBox="0 0 24 24"><path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm3 8H9V6a3 3 0 0 1 6 0z"/></svg></span>
            <input type="password" name="login_pass" placeholder="Mot de passe" required>
        </div>

        <button type="submit" class="login-btn">Se connecter</button>
        <div class="login-help">Matthieu Ballout / Noah Guichard · CIR2 · ISEN Ouest</div>
      </form>
    </div>
  </div>

</body>
</html>
<?php
exit;
