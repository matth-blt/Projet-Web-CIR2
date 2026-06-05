<?php
/*
 * Page de connexion du back-office (version simple).
 *
 * À inclure tout en haut de chaque page à protéger, AVANT tout affichage :
 *     require __DIR__ . '/auth.php';
 *
 * - Si l'utilisateur est connecté : la page continue normalement.
 * - Sinon : on affiche le formulaire de connexion et on arrête là.
 *
 * Les identifiants autorisés sont écrits EN DUR ci-dessous.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─────────────────────────────────────────────
//  Identifiants autorisés (en clair, à modifier)
// ─────────────────────────────────────────────
$COMPTES = [
    'matthieu' => 'irve-matthieu',
    'noah'     => 'irve-noah',
];

// ── Déconnexion : lien ?logout=1 ──
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ── Vérification du formulaire ──
$erreur = false;
if (isset($_POST['login_user'], $_POST['login_pass'])) {
    $u = $_POST['login_user'];
    $p = $_POST['login_pass'];
    if (isset($COMPTES[$u]) && $COMPTES[$u] === $p) {
        $_SESSION['auth_user'] = $u;
        header('Location: ' . $_SERVER['REQUEST_URI']); // recharge la page demandée
        exit;
    }
    $erreur = true;
}

// ── Déjà connecté ? On laisse passer la page. ──
if (!empty($_SESSION['auth_user'])) {
    return;
}

// Chemin vers le dossier assets, valable depuis back/ comme depuis back/php/.
$base = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'php') ? '../' : '';

// ── Sinon : page de connexion, puis on stoppe tout. ──
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — IRVE Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --blue-dark:#1c2b3a; --blue-accent:#4a8fc1;
      --surface2:#eef2f7;
      --text:#111827; --text2:#4a5568; --text3:#8898aa; --border:#d5dde6;
    }
    *{box-sizing:border-box; margin:0; padding:0;}
    body{font-family:'DM Sans',sans-serif; color:var(--text); min-height:100vh; background:#fff; display:flex;}

    .split{width:100%; min-height:100vh; display:flex;}

    /* ── Gauche : illustration ── */
    .split-art{flex:1; display:flex; align-items:center; justify-content:center; padding:32px;}
    .split-art img{
      width:auto; max-width:100%; max-height:82vh; height:auto; display:block;
      filter:drop-shadow(0 16px 30px rgba(28,43,58,.12));
    }

    /* ── Droite : formulaire ── */
    .split-form{flex:1; display:flex; align-items:center; justify-content:center; padding:48px;}
    .form-inner{width:100%; max-width:360px;}

    .login-title{text-align:center; font-size:40px; font-weight:700; letter-spacing:.02em; color:var(--blue-dark); margin-bottom:6px; text-transform:uppercase;}
    .login-sub{text-align:center; font-size:14px; color:var(--text3); margin-bottom:30px;}
    .login-err{background:#fff0f0; border:1px solid #e24b4a; color:#c0392b; font-size:14px; padding:11px 14px; border-radius:10px; margin-bottom:20px; text-align:center;}

    .field{
      display:flex; align-items:stretch; background:var(--surface2);
      border:1px solid var(--border); border-radius:14px; overflow:hidden; margin-bottom:16px;
      transition:border-color .15s, box-shadow .15s;
    }
    .field:focus-within{border-color:var(--blue-accent); box-shadow:0 0 0 3px rgba(74,143,193,.15);}
    .field-ico{flex:0 0 56px; background:var(--blue-accent); display:flex; align-items:center; justify-content:center;}
    .field-ico svg{width:20px; height:20px; fill:#fff;}
    .field input{flex:1; border:none; background:transparent; padding:15px 16px; font-family:inherit; font-size:15px; color:var(--text);}
    .field input:focus{outline:none;}
    .field input::placeholder{color:var(--text3);}

    .login-btn{
      display:block; width:100%; margin:26px auto 0; cursor:pointer;
      background:var(--blue-accent); color:#fff; border:none; border-radius:30px; padding:15px;
      font-family:inherit; font-size:15px; font-weight:600; letter-spacing:.04em; text-transform:uppercase;
      box-shadow:0 10px 22px rgba(74,143,193,.38); transition:background .15s, transform .05s;
    }
    .login-btn:hover{background:#3f7fad;}
    .login-btn:active{transform:translateY(1px);}
    .login-help{text-align:center; margin-top:24px; font-size:13px; color:var(--text3);}

    @media (max-width:860px){
      .split-art{display:none;}
      .split-form{flex:1 1 100%; padding:40px 28px;}
    }
  </style>
</head>
<body>

  <div class="split">
    <div class="split-art">
      <img src="<?= $base ?>assets/img-trim.png" alt="Borne de recharge IRVE">
    </div>

    <div class="split-form">
      <form class="form-inner" method="POST" action="">
        <div class="login-title">Connexion</div>
        <div class="login-sub">IRVE Admin — Back-office</div>

        <?php if ($erreur): ?>
          <div class="login-err">Identifiant ou mot de passe incorrect.</div>
        <?php endif; ?>

        <div class="field">
          <span class="field-ico"><svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/></svg></span>
          <input type="text" name="login_user" placeholder="Identifiant" autofocus required
                 value="<?= htmlspecialchars($_POST['login_user'] ?? '') ?>">
        </div>
        <div class="field">
          <span class="field-ico"><svg viewBox="0 0 24 24"><path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm3 8H9V6a3 3 0 0 1 6 0z"/></svg></span>
          <input type="password" name="login_pass" placeholder="Mot de passe" required>
        </div>

        <button type="submit" class="login-btn">Se connecter</button>
        <div class="login-help">Accès réservé à l'administration · ISEN Ouest</div>
      </form>
    </div>
  </div>

</body>
</html>
<?php
exit;
