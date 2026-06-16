<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { // (ou 'technicien' / 'admin')
    header("Location: /projet/ticket/login"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HelpDesk AI — Dashboard Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.7.0/dist/tabler-icons.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f7; display: flex; min-height: 100vh; }
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
    .badge-sb { background: #E24B4A; color: #fff; font-size: 10px; padding: 1px 6px; border-radius: 20px; margin-left: auto; }

    /* Main */
    .main { flex: 1; background: #f4f5f7; overflow-y: auto; }
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.75rem; background: #fff; border-bottom: 1px solid #e5e7eb; }
    .tb-title { font-size: 17px; font-weight: 600; color: #1e2a3a; }
    .av { width: 32px; height: 32px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #185FA5; }
    .body { padding: 1.5rem 1.75rem; }

    /* Metrics */
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.5rem; }
    .mc { background: #fff; border-radius: 10px; padding: .875rem 1rem; border: 1px solid #e5e7eb; }
    .mc-l { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .mc-v { font-size: 24px; font-weight: 600; color: #1e2a3a; }
    .mc-s { font-size: 11px; margin-top: 4px; }

    /* Cards */
    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; }

    /* Bar chart */
    .bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .bar-label { width: 80px; font-size: 12px; color: #6b7280; text-align: right; flex-shrink: 0; }
    .bar-bg { flex: 1; height: 9px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 4px; }
    .bar-val { font-size: 12px; color: #6b7280; min-width: 28px; text-align: right; }

    /* Users */
    .urow { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .urow:last-child { border-bottom: none; }
    .uav { width: 30px; height: 30px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #185FA5; flex-shrink: 0; }
    .urole { font-size: 11px; color: #9ca3af; }
    .ust { font-size: 11px; padding: 2px 8px; border-radius: 20px; }
    .st-on { background: #E1F5EE; color: #0F6E56; }
    .st-off { background: #FAEEDA; color: #854F0B; }

    /* Ticket rows */
    .tix-row { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .tix-row:last-child { border-bottom: none; }
    .tid { font-size: 12px; color: #9ca3af; width: 54px; flex-shrink: 0; }
    .tdesc { font-size: 13px; color: #1e2a3a; flex: 1; }
    .tcat { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .cat-net { background: #E6F1FB; color: #185FA5; }
    .cat-hw  { background: #FAEEDA; color: #854F0B; }
    .cat-sw  { background: #EEEDFE; color: #534AB7; }
    .cat-acc { background: #E1F5EE; color: #0F6E56; }
    .tpr { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .pr-urg  { background: #FCEBEB; color: #A32D2D; }
    .pr-haut { background: #FAEEDA; color: #854F0B; }
    .pr-norm { background: #f3f4f6; color: #6b7280; }
  </style>
</head>
<body>
  <div class="app">
    <div class="sb">
      <div class="sb-logo">
        <div class="sb-icon"><i class="ti ti-headset"></i></div>
        <span class="sb-name">HelpDesk AI</span>
      </div>
      <div class="sb-sec">Administration</div>
      <div class="sbi on"><i class="ti ti-layout-dashboard"></i>Vue globale</div>
      <div class="sbi"><i class="ti ti-ticket"></i>Tous les tickets<span class="badge-sb">12</span></div>
      <div class="sbi"><i class="ti ti-users"></i>Utilisateurs</div>
      <div class="sbi"><i class="ti ti-tool"></i>Techniciens</div>
      <div class="sb-sec">Système</div>
      <div class="sbi"><i class="ti ti-cpu"></i>Modèle IA</div>
      <div class="sbi"><i class="ti ti-chart-bar"></i>Rapports</div>
      <div class="sbi"><i class="ti ti-settings"></i>Paramètres</div>
      <a href="/projet/ticket/logout" style="text-decoration: none;">
        <div class="sbi" style="color: #E24B4A;"><i class="ti ti-logout"></i>Déconnexion</div>
      </a>
    </div>
    <div class="main">
      <div class="topbar">
        <span class="tb-title">Vue globale — Admin</span>
        <div style="display:flex;align-items:center;gap:12px">
          <i class="ti ti-bell" style="font-size:18px;color:#6b7280"></i>
          <div class="av">AD</div>
          <span style="font-size:13px;color:#1e2a3a">Admin</span>
        </div>
      </div>
      <div class="body">
        <div class="metrics">
          <div class="mc"><div class="mc-l">Tickets totaux</div><div class="mc-v">47</div><div class="mc-s" style="color:#6b7280">Ce mois</div></div>
          <div class="mc"><div class="mc-l">Ouverts</div><div class="mc-v">12</div><div class="mc-s" style="color:#A32D2D">3 urgents</div></div>
          <div class="mc"><div class="mc-l">Résolus</div><div class="mc-v">35</div><div class="mc-s" style="color:#0F6E56">74% taux</div></div>
          <div class="mc"><div class="mc-l">Précision IA</div><div class="mc-v">91%</div><div class="mc-s" style="color:#185FA5">Catégorisation</div></div>
        </div>
        <div class="row2">
          <div class="card">
            <div class="ch">Tickets par catégorie</div>
            <div class="bar-row"><span class="bar-label">Réseau</span><div class="bar-bg"><div class="bar-fill" style="width:72%;background:#378ADD"></div></div><span class="bar-val">18</span></div>
            <div class="bar-row"><span class="bar-label">Logiciel</span><div class="bar-bg"><div class="bar-fill" style="width:52%;background:#7F77DD"></div></div><span class="bar-val">13</span></div>
            <div class="bar-row"><span class="bar-label">Matériel</span><div class="bar-bg"><div class="bar-fill" style="width:36%;background:#EF9F27"></div></div><span class="bar-val">9</span></div>
            <div class="bar-row"><span class="bar-label">Accès / AD</span><div class="bar-bg"><div class="bar-fill" style="width:28%;background:#1D9E75"></div></div><span class="bar-val">7</span></div>
          </div>
          <div class="card">
            <div class="ch">Techniciens actifs</div>
            <div class="urow"><div class="uav">KM</div><div style="flex:1"><div style="font-size:13px;color:#1e2a3a;font-weight:500">Karim Mansouri</div><div class="urole">Réseau — 4 tickets</div></div><span class="ust st-on">Disponible</span></div>
            <div class="urow"><div class="uav">SL</div><div style="flex:1"><div style="font-size:13px;color:#1e2a3a;font-weight:500">Sara Lamine</div><div class="urole">Logiciel — 3 tickets</div></div><span class="ust st-on">Disponible</span></div>
            <div class="urow"><div class="uav">AM</div><div style="flex:1"><div style="font-size:13px;color:#1e2a3a;font-weight:500">Amine Mekki</div><div class="urole">Matériel — 5 tickets</div></div><span class="ust st-off">Occupé</span></div>
            <div class="urow"><div class="uav">NB</div><div style="flex:1"><div style="font-size:13px;color:#1e2a3a;font-weight:500">Nadia Brahimi</div><div class="urole">Accès / AD — 2 tickets</div></div><span class="ust st-on">Disponible</span></div>
          </div>
        </div>
        <div class="card">
          <div class="ch">Tickets récents</div>
          <div class="tix-row"><span class="tid">#T-0047</span><span class="tdesc">VPN ne fonctionne plus depuis mise à jour</span><span class="tcat cat-net">Réseau</span><span class="tpr pr-urg">Urgent</span></div>
          <div class="tix-row"><span class="tid">#T-0046</span><span class="tdesc">Logiciel comptabilité crash au démarrage</span><span class="tcat cat-sw">Logiciel</span><span class="tpr pr-haut">Haute</span></div>
          <div class="tix-row"><span class="tid">#T-0045</span><span class="tdesc">Compte AD bloqué après tentatives</span><span class="tcat cat-acc">Accès</span><span class="tpr pr-urg">Urgent</span></div>
          <div class="tix-row"><span class="tid">#T-0044</span><span class="tdesc">Imprimante bureau RH hors ligne</span><span class="tcat cat-hw">Matériel</span><span class="tpr pr-norm">Normale</span></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>