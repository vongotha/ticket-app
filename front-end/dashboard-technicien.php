<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technicien') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HelpDesk AI — Dashboard Technicien</title>
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

    /* Grid */
    .row2 { display: grid; grid-template-columns: 1fr 1.5fr; gap: 14px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; }

    /* Queue rows */
    .trow { display: flex; align-items: center; gap: 8px; padding: 8px 6px; border-bottom: 1px solid #f3f4f6; cursor: pointer; border-radius: 8px; transition: background .12s; }
    .trow:hover { background: #f9fafb; }
    .trow.sel { background: #E6F1FB; border-color: transparent; }
    .trow:last-child { border-bottom: none; }
    .tid { font-size: 12px; color: #9ca3af; width: 54px; flex-shrink: 0; }
    .tdesc { font-size: 13px; color: #1e2a3a; flex: 1; }
    .tcat { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .cat-net { background: #E6F1FB; color: #185FA5; }
    .tpr { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .pr-urg  { background: #FCEBEB; color: #A32D2D; }
    .pr-haut { background: #FAEEDA; color: #854F0B; }
    .pr-norm { background: #f3f4f6; color: #6b7280; }

    /* Detail panel */
    .detail-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; }
    .ai-box { background: #E6F1FB; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; }
    .ai-box-title { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #185FA5; margin-bottom: 4px; }
    .ai-box-text { font-size: 12px; color: #0C447C; line-height: 1.6; }
    .info-block { border: 1px solid #e5e7eb; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; }
    .info-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 12px; color: #6b7280; }
    .info-val { font-size: 12px; color: #1e2a3a; font-weight: 500; }
    textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; outline: none; resize: none; font-family: inherit; transition: border .15s; }
    textarea:focus { border-color: #378ADD; }
    .action-row { display: flex; gap: 8px; margin-top: .875rem; }
    .abtn { flex: 1; padding: 9px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #6b7280; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: background .15s; font-family: inherit; }
    .abtn:hover { background: #f9fafb; }
    .abtn-prim { background: #1e2a3a; color: #fff; border-color: #1e2a3a; }
    .abtn-prim:hover { background: #378ADD; border-color: #378ADD; }
    .badge { font-size: 11px; padding: 2px 8px; border-radius: 20px; }
    .b-urg { background: #FCEBEB; color: #A32D2D; }
    .b-prog { background: #E6F1FB; color: #185FA5; }
  </style>
</head>
<body>
  <div class="app">
    <div class="sb">
      <div class="sb-logo">
        <div class="sb-icon"><i class="ti ti-headset"></i></div>
        <span class="sb-name">HelpDesk AI</span>
      </div>
      <div class="sb-sec">Technicien</div>
      <div class="sbi on"><i class="ti ti-layout-dashboard"></i>Mes tickets<span class="badge-sb">4</span></div>
      <div class="sbi"><i class="ti ti-check"></i>Résolus</div>
      <div class="sb-sec">Compte</div>
      <div class="sbi"><i class="ti ti-bell"></i>Notifications</div>
      <div class="sbi"><i class="ti ti-user"></i>Mon profil</div>
    </div>
    <div class="main">
      <div class="topbar">
        <span class="tb-title">Mes tickets assignés</span>
        <div style="display:flex;align-items:center;gap:12px">
          <i class="ti ti-bell" style="font-size:18px;color:#6b7280"></i>
          <div class="av">KM</div>
          <span style="font-size:13px;color:#1e2a3a">Karim Mansouri</span>
        </div>
      </div>
      <div class="body">
        <div class="metrics">
          <div class="mc"><div class="mc-l">Assignés</div><div class="mc-v">4</div><div class="mc-s" style="color:#6b7280">Actifs</div></div>
          <div class="mc"><div class="mc-l">Urgents</div><div class="mc-v">1</div><div class="mc-s" style="color:#A32D2D">À traiter</div></div>
          <div class="mc"><div class="mc-l">Résolus ce mois</div><div class="mc-v">22</div><div class="mc-s" style="color:#0F6E56">Fermés</div></div>
          <div class="mc"><div class="mc-l">Délai moyen</div><div class="mc-v">3h20</div><div class="mc-s" style="color:#6b7280">Résolution</div></div>
        </div>
        <div class="row2">
          <div class="card">
            <div class="ch">File d'attente</div>
            <div class="trow sel" onclick="selectTicket(this)"><span class="tid">#T-0047</span><span class="tdesc">VPN ne fonctionne plus</span><span class="tcat cat-net">Réseau</span><span class="tpr pr-urg">Urgent</span></div>
            <div class="trow" onclick="selectTicket(this)"><span class="tid">#T-0043</span><span class="tdesc">Lenteurs WiFi open space</span><span class="tcat cat-net">Réseau</span><span class="tpr pr-haut">Haute</span></div>
            <div class="trow" onclick="selectTicket(this)"><span class="tid">#T-0040</span><span class="tdesc">Switch bureau 3 hors service</span><span class="tcat cat-net">Réseau</span><span class="tpr pr-norm">Normale</span></div>
            <div class="trow" onclick="selectTicket(this)"><span class="tid">#T-0037</span><span class="tdesc">Accès partage réseau refusé</span><span class="tcat cat-net">Réseau</span><span class="tpr pr-norm">Normale</span></div>
          </div>
          <div class="card">
            <div class="detail-header">
              <div>
                <div class="trow sel" data-id="47" onclick="chargerDetailsTicket(this)">
                  <span class="tid">#T-0047</span>
                  <span class="tdesc">VPN ne -- fonctionne plus</span>
                  <span class="tcat cat-net">Réseau</span>
                  <span class="tpr pr-urg">Urgent</span>
                </div>
                <div style="font-size:13px;color:#6b7280">Soumis par Yasmine Bouri · il y a 2h</div>
              </div>
            </div>
            <div class="ai-box">
              <div class="ai-box-title"><i class="ti ti-cpu" style="font-size:14px"></i>Analyse IA</div>
              <div class="ai-box-text">Catégorie détectée : <strong>Réseau — VPN</strong>. Assigné automatiquement à Karim Mansouri (spécialiste réseau). Confiance : <strong>94%</strong>.</div>
            </div>
            <div style="margin-bottom:1rem">
              <div style="font-size:13px;font-weight:600;color:#1e2a3a;margin-bottom:.5rem">VPN ne fonctionne plus depuis mise à jour</div>
              <div style="font-size:13px;color:#6b7280;line-height:1.6">Depuis ce matin après la mise à jour Windows, je ne peux plus me connecter au VPN de l'entreprise. L'erreur affichée est "Authentication failed". J'ai redémarré plusieurs fois sans succès.</div>
            </div>
            <div class="info-block">
              <div style="font-size:12px;color:#6b7280;margin-bottom:8px;font-weight:500">Informations du ticket</div>
              <div class="info-row"><span class="info-label">Demandeur</span><span class="info-val">Yasmine Bouri</span></div>
              <div class="info-row"><span class="info-label">Catégorie IA</span><span class="info-val">Réseau — VPN</span></div>
              <div class="info-row"><span class="info-label">Créé le</span><span class="info-val">13/06/2026 à 09h14</span></div>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:6px">Note de résolution</div>
            <textarea rows="2" placeholder="Décrivez la solution apportée..."></textarea>
            <div class="action-row">
              <button class="abtn"><i class="ti ti-arrow-back" style="font-size:14px"></i>Retour</button>
              <button class="abtn"><i class="ti ti-clock" style="font-size:14px"></i>En attente</button>
              <button class="abtn abtn-prim"><i class="ti ti-check" style="font-size:14px"></i>Marquer résolu</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script type="module" src="chargerTicket.js"></script>
  <script>
    function selectTicket(el) {
      document.querySelectorAll('.trow').forEach(r => r.classList.remove('sel'));
      el.classList.add('sel');
    }
  </script>
</body>
</html>