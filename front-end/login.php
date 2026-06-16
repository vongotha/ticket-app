<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HelpDesk AI — Connexion</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.7.0/dist/tabler-icons.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f7; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .wrap { display: flex; width: 900px; min-height: 520px; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10); }
    .left { flex: 1; background: #1e2a3a; display: flex; flex-direction: column; justify-content: center; padding: 2.5rem; }
    .right { flex: 1; background: #fff; display: flex; flex-direction: column; justify-content: center; padding: 2.5rem; }
    .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 2.5rem; }
    .logo-box { width: 38px; height: 38px; background: #378ADD; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .logo-box i { color: #fff; font-size: 20px; }
    .logo-name { color: #fff; font-size: 18px; font-weight: 600; }
    .ai-badge { display: inline-flex; align-items: center; gap: 6px; background: #0C447C; border-radius: 20px; padding: 4px 14px; font-size: 12px; color: #B5D4F4; margin-bottom: 1.5rem; }
    .tagline { color: #85B7EB; font-size: 13px; line-height: 1.7; margin-bottom: 1.5rem; }
    .feat { display: flex; align-items: center; gap: 10px; margin-bottom: .9rem; }
    .feat i { color: #378ADD; font-size: 17px; }
    .feat span { color: #B5D4F4; font-size: 13px; }
    .ftitle { font-size: 22px; font-weight: 600; color: #1e2a3a; margin-bottom: 4px; }
    .fsub { font-size: 13px; color: #6b7280; margin-bottom: 1.75rem; }
    .flabel { font-size: 12px; color: #6b7280; margin-bottom: 6px; display: block; }
    .fgroup { margin-bottom: 1rem; }
    .role-row { display: flex; gap: 8px; margin-bottom: 1.25rem; }
    .rbtn { flex: 1; padding: 9px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #6b7280; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all .15s; }
    .rbtn.active { border-color: #378ADD; background: #E6F1FB; color: #185FA5; font-weight: 500; }
    input, select, textarea { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; transition: border .15s; }
    input:focus, select:focus, textarea:focus { border-color: #378ADD; }
    .sbtn { width: 100%; padding: 11px; background: #1e2a3a; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; margin-top: .5rem; transition: background .15s; }
    .sbtn:hover { background: #378ADD; }
    .forgot { text-align: right; font-size: 12px; color: #378ADD; margin-top: 6px; cursor: pointer; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="left">
      <div class="logo">
        <div class="logo-box"><i class="ti ti-headset"></i></div>
        <span class="logo-name">HelpDesk AI</span>
      </div>
      <div class="ai-badge"><i class="ti ti-cpu" style="font-size:14px"></i> Catégorisation automatique par IA</div>
      <p class="tagline">Plateforme intelligente de gestion de tickets IT pour les équipes d'entreprise.</p>
      <div class="feat"><i class="ti ti-bolt"></i><span>Assignation automatique au bon technicien</span></div>
      <div class="feat"><i class="ti ti-tag"></i><span>Catégorisation IA selon le problème</span></div>
      <div class="feat"><i class="ti ti-chart-line"></i><span>Suivi en temps réel des tickets</span></div>
    <div class="feat"><i class="ti ti-users"></i><span>Rôles : Employé, Technicien, Admin</span></div>
    </div>
    <div class="right">
      <p class="ftitle">Connexion</p>
      <p class="fsub">Accédez à votre espace de travail</p>
     <div style="margin-bottom:1rem">
        <!--<span class="flabel">Rôle</span>-->
        <div class="role-row">
          <!--<button class="rbtn" onclick="setR(this)"><i class="ti ti-user"></i>Employé</button>
          <button class="rbtn" onclick="setR(this)"><i class="ti ti-tool"></i>Technicien</button>
          <button class="rbtn active" onclick="setR(this)"><i class="ti ti-shield"></i>Admin</button>-->
        </div>
      </div>
      <div class="fgroup">
        <label class="flabel" for="em">Adresse email</label>
        <input type="email" id="em" placeholder="prenom.nom@entreprise.fr">
      </div>
      <div class="fgroup">
        <label class="flabel" for="pw">Mot de passe</label>
        <input type="password" id="pw" placeholder="••••••••">
      </div>
      <p class="forgot">Mot de passe oublié ?</p>
      <p id="error-msg" style="color: #dc2626; font-size: 13px; margin-bottom: 10px; display: none;"></p>
      <button class="sbtn" id="login-btn">Se connecter</button>
    </div>
  </div>
  <script type="module" src="login.js"></script>
  <script>
    function setR(el) {
      document.querySelectorAll('.rbtn').forEach(b => b.classList.remove('active'));
      el.classList.add('active');
    }
  </script>
</body>
</html>