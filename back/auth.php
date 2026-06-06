<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $USERS = [
        'matthieu' => '$2y$12$wshtRls.2HJZ4CeSF/qBnuvhKOoFaoHQ/3mnAxBeEa2884dXKSAEG',
        'noah' => '$2y$12$TIBgf0gDMR1zeIxkQNEAUO40JSPxvwx1YTIyWxf.kQ6e9O1fUGMES'
    ];

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if (isset($_GET['logout'])) {
        $_SESSION = [];
        session_destroy();
?>      <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <script>
                localStorage.removeItem('auth_user');
                window.location.href = <?= json_encode(strtok($_SERVER["REQUEST_URI"], "?")) ?>;
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
        $csrf = $_POST['csrf_token'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            die('Erreur CSRF : Action non autorisée.');
        }

        $u = $_POST['login_user'];
        $p = $_POST['login_pass'];
        if (isset($USERS[$u]) && password_verify($p, $USERS[$u])) {
            $_SESSION['auth_user'] = $u;
?>          <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <script>
                    localStorage.setItem('auth_user', <?= json_encode($u) ?>);
                    window.location.href = <?= json_encode($_SERVER['REQUEST_URI']) ?>;
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
            <span class="field-ico">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24" ><path d="M12 2a5 5 0 1 0 0 10 5 5 0 1 0 0-10M4 22h16c.55 0 1-.45 1-1v-1c0-3.86-3.14-7-7-7h-4c-3.86 0-7 3.14-7 7v1c0 .55.45 1 1 1"></path></svg>
            </span>
            <input type="text" name="login_user" placeholder="Identifiant" autofocus required value="<?= htmlspecialchars($_POST['login_user'] ?? '') ?>">
        </div>
        <div class="field">
            <span class="field-ico">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24" ><path d="M6 22h12c1.1 0 2-.9 2-2v-9c0-1.1-.9-2-2-2h-1V7c0-2.76-2.24-5-5-5S7 4.24 7 7v2H6c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2M9 7c0-1.65 1.35-3 3-3s3 1.35 3 3v2H9z"></path></svg>
            </span>
            <input type="password" name="login_pass" placeholder="Mot de passe" required>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <button type="submit" class="login-btn">Se connecter</button>
        <div class="login-help">Matthieu Ballout / Noah Guichard · CIR2 · ISEN Ouest</div>
      </form>
    </div>
  </div>

</body>
</html>
<?php
exit;
