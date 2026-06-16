<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

//Utiliser la route propre du routeur /projet/ticket/login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header("Location: /projet/ticket/login"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HelpDesk AI — Dashboard Employé</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.7.0/dist/tabler-icons.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f7; display: flex; align-items: stretch; min-height: 100vh; }
    .app { display: flex; width: 100%; min-height: 100vh; }

    /* Sidebar */
    .sb { width: 210px; background: #1e2a3a; padding: 1.5rem 1rem; flex-shrink: 0; }
    .sb-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
    .sb-icon { width: 30px; height: 30px; background: #378ADD; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
    .sb-icon i { color: #fff; font-size: 16px; }
    .sb-name { color: #fff; font-weight: 600; font-size: 15px; }
    .sb-sec { font-size: 11px; color: #378ADD; text-transform: uppercase; letter-spacing: .06em; margin: 1.25rem 0 .4rem; padding-left: 8px; }
    .sbi { display: flex; align-items: center; gap: 8px; padding: 9px 8px; border-radius: 8px; color: #85B7EB; margin-bottom: 2px; cursor: pointer; font-size: 14px; transition: background .15s; }
    .sbi:hover { background: rgba(55,138,221,.12); }
    .sbi.on { background: rgba(55,138,221,.2); color: #fff; }
    .sbi i { font-size: 17px; }

    /* Main */
    .main { flex: 1; background: #f4f5f7; overflow-y: auto; }
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.75rem; background: #fff; border-bottom: 1px solid #e5e7eb; }
    .tb-title { font-size: 17px; font-weight: 600; color: #1e2a3a; }
    .tb-right { display: flex; align-items: center; gap: 12px; }
    .av { width: 32px; height: 32px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #185FA5; }
    .body { padding: 1.5rem 1.75rem; }

    /* Metrics */
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.5rem; }
    .mc { background: #fff; border-radius: 10px; padding: .875rem 1rem; border: 1px solid #e5e7eb; }
    .mc-l { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .mc-v { font-size: 24px; font-weight: 600; color: #1e2a3a; }
    .mc-s { font-size: 11px; margin-top: 4px; }

    /* Grid */
    .row2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 14px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; }

    /* Ticket rows */
    .trow { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .trow:last-child { border-bottom: none; }
    .tid { font-size: 12px; color: #9ca3af; width: 54px; flex-shrink: 0; }
    .tdesc { font-size: 13px; color: #1e2a3a; flex: 1; }
    .tcat { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .cat-net { background: #E6F1FB; color: #185FA5; }
    .cat-hw  { background: #FAEEDA; color: #854F0B; }
    .cat-sw  { background: #EEEDFE; color: #534AB7; }
    .cat-acc { background: #E1F5EE; color: #0F6E56; }
    .tst { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .st-open { background: #FCEBEB; color: #A32D2D; }
    .st-prog { background: #E6F1FB; color: #185FA5; }
    .st-done { background: #E1F5EE; color: #0F6E56; }

    /* Form */
    .flabel { font-size: 12px; color: #6b7280; margin-bottom: 5px; display: block; }
    .fg { margin-bottom: .875rem; }
    input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; outline: none; transition: border .15s; font-family: inherit; }
    input:focus, select:focus, textarea:focus { border-color: #378ADD; }
    .ai-hint { display: flex; align-items: center; gap: 6px; background: #E6F1FB; border-radius: 8px; padding: 8px 10px; font-size: 12px; color: #185FA5; margin-bottom: .875rem; }
    .sbtn { width: 100%; padding: 9px; background: #1e2a3a; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: background .15s; }
    .sbtn:hover { background: #378ADD; }
  </style>
</head>
<body>
  <div class="app">
    <div class="sb">
      <div class="sb-logo">
        <div class="sb-icon"><i class="ti ti-headset"></i></div>
        <span class="sb-name">HelpDesk AI</span>
      </div>
      <div class="sb-sec">Menu</div>
      <div class="sbi on"><i class="ti ti-layout-dashboard"></i>Tableau de bord</div>
      <div class="sbi"><i class="ti ti-ticket"></i>Mes tickets</div>
      <div class="sbi"><i class="ti ti-plus"></i>Nouveau ticket</div>
      <div class="sb-sec">Compte</div>
      <div class="sbi"><i class="ti ti-bell"></i>Notifications</div>
      <div class="sbi"><i class="ti ti-user"></i>Mon profil</div>
    </div>
    <div class="main">
      <div class="topbar">
        <span class="tb-title">Bonjour, <?php echo htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur'); ?> 👋</span>
        <div class="tb-right">
          <i class="ti ti-bell" style="font-size:18px;color:#6b7280;cursor:pointer;"></i>
          <div class="av"><?php echo strtoupper(substr($_SESSION['nom'] ?? 'U', 0, 2)); ?></div>
          <span style="font-size:13px;color:#1e2a3a"><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur'); ?></span>
        </div>
      </div>
      <div class="body">
        <div class="metrics">
          <div class="mc"><div class="mc-l">Tickets ouverts</div><div class="mc-v">3</div><div class="mc-s" style="color:#A32D2D">2 en attente</div></div>
          <div class="mc"><div class="mc-l">En cours</div><div class="mc-v">1</div><div class="mc-s" style="color:#185FA5">Technicien assigné</div></div>
          <div class="mc"><div class="mc-l">Résolus ce mois</div><div class="mc-v">7</div><div class="mc-s" style="color:#0F6E56">Fermés</div></div>
          <div class="mc"><div class="mc-l">Délai moyen</div><div class="mc-v">4h</div><div class="mc-s" style="color:#6b7280">Résolution</div></div>
        </div>
        <div class="row2">
          <div class="card">
            <div class="ch">Mes tickets récents</div>
            <div class="trow"><span class="tid">#T-0041</span><span class="tdesc">Impossible de se connecter au VPN</span><span class="tcat cat-net">Réseau</span><span class="tst st-prog">En cours</span></div>
            <div class="trow"><span class="tid">#T-0038</span><span class="tdesc">Écran bleu au démarrage</span><span class="tcat cat-hw">Matériel</span><span class="tst st-open">Ouvert</span></div>
            <div class="trow"><span class="tid">#T-0035</span><span class="tdesc">Mise à jour Office bloquée</span><span class="tcat cat-sw">Logiciel</span><span class="tst st-open">Ouvert</span></div>
            <div class="trow"><span class="tid">#T-0029</span><span class="tdesc">Réinitialisation mot de passe AD</span><span class="tcat cat-acc">Accès</span><span class="tst st-done">Résolu</span></div>
            <div class="trow"><span class="tid">#T-0022</span><span class="tdesc">Imprimante réseau introuvable</span><span class="tcat cat-net">Réseau</span><span class="tst st-done">Résolu</span></div>
          </div>
          <div class="card">
            <div class="ch">Nouveau ticket</div>
            <div class="fg">
              <label class="flabel" for="tobj">Objet du problème</label>
              <input type="text" id="tobj" placeholder="Ex: Je ne peux pas accéder à...">
            </div>
            <div class="fg">
              <label class="flabel" for="tdesc2">Description détaillée</label>
              <textarea id="tdesc2" rows="3" placeholder="Décrivez le problème en détail..."></textarea>
            </div>
            <div class="fg">
              <label class="flabel" for="tprio">Priorité</label>
              <select id="tprio">
                <option>Faible</option>
                <option selected>Normale</option>
                <option>Haute</option>
                <option>Urgente</option>
              </select>
            </div>
            <div class="ai-hint"><i class="ti ti-cpu" style="font-size:14px"></i>L'IA va catégoriser et assigner ce ticket automatiquement</div>
            <button class="sbtn">Soumettre le ticket</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
<script type="module" src="/projet/ticket/front-end/createTicket.js"></script>
</html>