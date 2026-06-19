<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Sécurité
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technicien') {
    header("Location: /projet/ticket/login"); 
    exit();
}

require_once 'config/database.php';
$techId = $_SESSION['user_id'];

// =========================================================================
// 1. RÉCUPÉRATION DES MÉTRIQUES DU TECHNICIEN CONNECTÉ
// =========================================================================

// Tickets Actifs assignés (Non résolus)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE technicien_id = ? AND statut != 'Résolu'");
$stmt->execute([$techId]);
$ticketsAssignes = $stmt->fetchColumn();

// Tickets Urgents assignés
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE technicien_id = ? AND statut != 'Résolu' AND priorite = 'Urgente'");
$stmt->execute([$techId]);
$ticketsUrgents = $stmt->fetchColumn();

// Tickets Résolus par lui
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE technicien_id = ? AND statut = 'Résolu'");
$stmt->execute([$techId]);
$ticketsResolus = $stmt->fetchColumn();

// =========================================================================
// 2. FILE D'ATTENTE DE TRAVAIL (Non résolus)
// =========================================================================
$stmt = $pdo->prepare("
    SELECT t.*, u.nom AS client_nom 
    FROM tickets t 
    LEFT JOIN users u ON t.client_id = u.id 
    WHERE t.technicien_id = ? AND t.statut != 'Résolu' 
    ORDER BY CASE WHEN t.priorite = 'Urgente' THEN 1 WHEN t.priorite = 'Haute' THEN 2 ELSE 3 END, t.id DESC
");
$stmt->execute([$techId]);
$queueTickets = $stmt->fetchAll();

// =========================================================================
// 3. ARCHIVES DES TICKETS RÉSOLUS PAR LUI
// =========================================================================
$stmt = $pdo->prepare("
    SELECT t.*, u.nom AS client_nom 
    FROM tickets t 
    LEFT JOIN users u ON t.client_id = u.id 
    WHERE t.technicien_id = ? AND t.statut = 'Résolu' 
    ORDER BY t.id DESC
");
$stmt->execute([$techId]);
$resolvedHistory = $stmt->fetchAll();

// Mappings CSS de mise en forme
$prioClasses = ['Urgente' => 'pr-urg', 'Haute' => 'pr-haut', 'Normale' => 'pr-norm', 'Faible' => 'pr-norm'];
$catClasses  = ['Réseau' => 'cat-net', 'Matériel' => 'cat-hw', 'Logiciel' => 'cat-sw', 'Accès' => 'cat-acc', 'Email' => 'cat-em'];

// On sélectionne le premier ticket pour l'affichage de départ
$firstTicket = !empty($queueTickets) ? $queueTickets[0] : null;
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
    .sb { width: 210px; background: #1e2a3a; padding: 1.5rem 1rem; flex-shrink: 0; display: flex; flex-direction: column; }
    .sb-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
    .sb-icon { width: 30px; height: 30px; background: #378ADD; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
    .sb-icon i { color: #fff; font-size: 16px; }
    .sb-name { color: #fff; font-weight: 600; font-size: 15px; }
    .sb-sec { font-size: 11px; color: #378ADD; text-transform: uppercase; letter-spacing: .06em; margin: 1.25rem 0 .4rem; padding-left: 8px; }
    .sbi { display: flex; align-items: center; gap: 8px; padding: 9px 8px; border-radius: 8px; color: #85B7EB; margin-bottom: 2px; cursor: pointer; font-size: 14px; transition: background .15s; }
    .sbi:hover { background: rgba(55,138,221,.12); color: #fff; }
    .sbi.on { background: rgba(55,138,221,.2); color: #fff; }
    .sbi i { font-size: 17px; }
    .badge-sb { background: #E24B4A; color: #fff; font-size: 10px; padding: 1px 6px; border-radius: 20px; margin-left: auto; }
    
    /* Main Area */
    .main { flex: 1; background: #f4f5f7; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.75rem; background: #fff; border-bottom: 1px solid #e5e7eb; flex-shrink: 0; }
    .tb-title { font-size: 17px; font-weight: 600; color: #1e2a3a; }
    .av { width: 32px; height: 32px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #185FA5; }
    
    /* Vues */
    .body { padding: 1.5rem 1.75rem; flex: 1; overflow-y: auto; }
    .view-section { display: none; }
    .view-section.active { display: block; }

    /* Metrics */
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.5rem; }
    .mc { background: #fff; border-radius: 10px; padding: .875rem 1rem; border: 1px solid #e5e7eb; }
    .mc-l { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .mc-v { font-size: 24px; font-weight: 600; color: #1e2a3a; }
    .mc-s { font-size: 11px; margin-top: 4px; }
    
    /* Grids */
    .row2 { display: grid; grid-template-columns: 1fr 1.3fr; gap: 14px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; }
    
    /* Rows */
    .trow { display: flex; align-items: center; gap: 8px; padding: 10px 8px; border-bottom: 1px solid #f3f4f6; cursor: pointer; border-radius: 8px; transition: background .12s; }
    .trow:hover { background: #f9fafb; }
    .trow.sel { background: #E6F1FB; border-color: transparent; }
    .trow:last-child { border-bottom: none; }
    .tid { font-size: 12px; color: #9ca3af; width: 54px; flex-shrink: 0; }
    .tdesc { font-size: 13px; color: #1e2a3a; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tcat { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .cat-net { background: #E6F1FB; color: #185FA5; }
    .cat-hw  { background: #FFF0F0; color: #C53030; }
    .cat-sw  { background: #EBFBEE; color: #2B8A3E; }
    .cat-acc { background: #F3F0FF; color: #6741D9; }
    .cat-em  { background: #FCEBEB; color: #A32D2D; }
    .tpr { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .pr-urg  { background: #FCEBEB; color: #A32D2D; }
    .pr-haut { background: #FAEEDA; color: #854F0B; }
    .pr-norm { background: #f3f4f6; color: #6b7280; }
    
    /* Detail View Box */
    .detail-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; }
    .ai-box { background: #E6F1FB; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; }
    .ai-box-title { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #185FA5; margin-bottom: 4px; }
    .ai-box-text { font-size: 12px; color: #0C447C; line-height: 1.6; }
    .info-block { border: 1px solid #e5e7eb; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; }
    .info-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 12px; color: #6b7280; }
    .info-val { font-size: 12px; color: #1e2a3a; font-weight: 500; }
    
    textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; outline: none; resize: none; font-family: inherit; transition: border .15s; margin-bottom: .875rem; }
    textarea:focus { border-color: #378ADD; }
    .action-row { display: flex; gap: 8px; }
    .abtn { flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #6b7280; font-size: 12px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: background .15s; font-family: inherit; }
    .abtn:hover { background: #f9fafb; }
    .abtn-prim { background: #1e2a3a; color: #fff; border-color: #1e2a3a; }
    .abtn-prim:hover { background: #378ADD; border-color: #378ADD; }

    /* Notifications popup styling */
    .notif-bubble { display: none; position: absolute; top: 55px; right: 20px; width: 320px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); z-index: 100; max-height: 400px; overflow-y: auto; }
    .notif-header { padding: 10px 15px; border-bottom: 1px solid #f3f4f6; font-size: 13px; font-weight: 600; color: #1e2a3a; display: flex; justify-content: space-between; }
    .notif-item { padding: 12px 15px; border-bottom: 1px solid #f3f4f6; font-size: 12px; color: #4b5563; line-height: 1.4; position: relative; }
    .notif-item:last-child { border-bottom: none; }
    .notif-warn { color: #E24B4A; background: #FFF5F5; border-radius: 6px; padding: 6px; font-size: 11px; margin-top: 5px; display: flex; align-items: center; gap: 4px; }
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
      <div class="sbi on" onclick="switchView('active', this)"><i class="ti ti-layout-dashboard"></i>Tickets à traiter <span class="badge-sb" id="badge-count"><?= $ticketsAssignes; ?></span></div>
      <div class="sbi" onclick="switchView('resolved', this)"><i class="ti ti-check"></i>Historique résolu</div>
      
      <div class="sb-sec">Compte</div>
      <div class="sbi" onclick="toggleNotifications()"><i class="ti ti-bell"></i>Notifications</div>
      <a href="/projet/ticket/logout" class="sbi" style="color: #E24B4A; margin-top: auto;">
        <i class="ti ti-logout"></i>Déconnexion
      </a>
    </div>

    <div class="main">
      <div class="topbar">
        <span class="tb-title" id="topbar-title">Espace de travail — Tickets affectés</span>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="position:relative">
            <i class="ti ti-bell" id="bell-icon" style="font-size:18px;color:#6b7280;cursor:pointer;" onclick="toggleNotifications()"></i>
            
            <div class="notif-bubble" id="notif-box">
              <div class="notif-header">
                <span>Alertes Assignations</span>
                <span style="color:#378ADD;cursor:pointer;" onclick="markAllNotificationsRead()">Tout effacer</span>
              </div>
              <div id="notif-list">
                <div class="notif-item" style="text-align:center;color:#9ca3af">Aucune alerte récente</div>
              </div>
            </div>
          </div>
          <div class="av"><?= strtoupper(substr($_SESSION['nom'] ?? 'T', 0, 2)); ?></div>
          <span style="font-size:13px;color:#1e2a3a"><?= htmlspecialchars($_SESSION['nom'] ?? 'Technicien'); ?></span>
        </div>
      </div>

      <div class="body">
        <!-- VUE 1 : TICKETS ACTIFS / À TRAITER -->
        <div id="view-active" class="view-section active">
          <div class="metrics">
            <div class="mc"><div class="mc-l">Mes Assignations</div><div class="mc-v"><?= $ticketsAssignes; ?></div><div class="mc-s" style="color:#185FA5">En attente d'action</div></div>
            <div class="mc"><div class="mc-l">Urgences absolues</div><div class="mc-v" style="color:#A32D2D"><?= $ticketsUrgents; ?></div><div class="mc-s" style="color:#A32D2D">Priorité Haute/Urgent</div></div>
            <div class="mc"><div class="mc-l">Résolus ce mois</div><div class="mc-v"><?= $ticketsResolus; ?></div><div class="mc-s" style="color:#0F6E56">Total clôturés</div></div>
            <div class="mc"><div class="mc-l">Précision IA d'attribution</div><div class="mc-v">94%</div><div class="mc-s" style="color:#6b7280">Efficacité routage</div></div>
          </div>

          <div class="row2">
            <div class="card">
              <div class="ch">File d'attente de résolution</div>
              <div style="max-height: 480px; overflow-y: auto;">
                <?php if (empty($queueTickets)): ?>
                  <div style="text-align:center; padding:20px; color:#6b7280;">Aucun incident en cours dans votre file.</div>
                <?php else: ?>
                  <?php foreach ($queueTickets as $index => $t): ?>
                    <div class="trow <?= $index === 0 ? 'sel' : ''; ?>" data-id="<?= $t['id']; ?>" onclick="selectTicket(this)">
                      <span class="tid">#T-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></span>
                      <span class="tdesc"><strong><?= htmlspecialchars($t['titre']); ?></strong></span>
                      <span class="tcat <?= $catClasses[$t['categorie'] ?? 'Logiciel'] ?? 'cat-sw'; ?>"><?= htmlspecialchars($t['categorie'] ?? 'Logiciel'); ?></span>
                      <span class="tpr <?= $prioClasses[$t['priorite'] ?? 'Normale'] ?? 'pr-norm'; ?>"><?= htmlspecialchars($t['priorite'] ?? 'Normale'); ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- PANNEAU DROIT : DÉTAILS ET INTERACTION -->
            <div class="card" id="detail-pane" style="<?= !$firstTicket ? 'display:none;' : ''; ?>">
              <div class="detail-header">
                <div>
                  <h3 id="det-id" style="color:#1e2a3a; font-size:16px;">#T-<?= $firstTicket ? str_pad($firstTicket['id'], 4, '0', STR_PAD_LEFT) : ''; ?></h3>
                  <div id="det-meta" style="font-size:13px;color:#6b7280; margin-top:4px;">
                    Soumis par <?= htmlspecialchars($firstTicket['client_nom'] ?? 'Demandeur'); ?>
                  </div>
                </div>
              </div>
              
              <div class="ai-box">
                <div class="ai-box-title"><i class="ti ti-cpu" style="font-size:14px"></i>Analyse de Routage IA</div>
                <div class="ai-box-text" id="det-ai-text">
                  Catégorisateur : <strong><?= htmlspecialchars($firstTicket['categorie'] ?? 'Non catégorisé'); ?></strong>. Confiance calculée : <strong><?= $firstTicket['score_ia'] ?? '90'; ?>%</strong>.
                </div>
              </div>

              <div style="margin-bottom:1rem">
                <div id="det-title" style="font-size:13px;font-weight:600;color:#1e2a3a;margin-bottom:.5rem"><?= htmlspecialchars($firstTicket['titre'] ?? ''); ?></div>
                <div id="det-desc" style="font-size:13px;color:#6b7280;line-height:1.6; max-height: 120px; overflow-y:auto;"><?= nl2br(htmlspecialchars($firstTicket['description'] ?? '')); ?></div>
              </div>

              <div class="info-block">
                <div class="info-row"><span class="info-label">Utilisateur à dépanner</span><span class="info-val" id="det-client-row"><?= htmlspecialchars($firstTicket['client_nom'] ?? 'Inconnu'); ?></span></div>
                <div class="info-row"><span class="info-label">Catégorie assignée</span><span class="info-val" id="det-cat-row"><?= htmlspecialchars($firstTicket['categorie'] ?? 'Logiciel'); ?></span></div>
                <div class="info-row"><span class="info-label">Criticité</span><span class="info-val" id="det-prio-row"><?= htmlspecialchars($firstTicket['priorite'] ?? 'Normale'); ?></span></div>
              </div>

              <div style="font-size:12px;color:#6b7280;margin-bottom:6px;font-weight:500">Note de résolution (Sera partagée par e-mail avec l'employé)</div>
              <textarea id="note-resolution" rows="3" placeholder="Indiquez la cause ainsi que la solution appliquée pour clore ce ticket..."></textarea>
              
              <div class="action-row">
                <button class="abtn" onclick="setAsPending()"><i class="ti ti-clock"></i>Mettre en attente</button>
                <button class="abtn abtn-prim" id="btn-resolve"><i class="ti ti-check"></i>Marquer comme résolu</button>
              </div>
            </div>
            
            <?php if (!$firstTicket): ?>
              <div class="card" style="display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:13px; text-align:center;">
                 Tous vos tickets ont été traités. Félicitations !
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- VUE 2 : ARCHIVES DE TOUS LES TICKETS RÉSOLUS -->
        <div id="view-resolved" class="view-section">
          <div class="card">
            <div class="ch">Historique de vos interventions clôturées</div>
            <div style="max-height: 520px; overflow-y: auto;">
              <?php if (empty($resolvedHistory)): ?>
                <div style="text-align:center; padding:30px; color:#6b7280;">Aucune archive de ticket résolu pour le moment.</div>
              <?php else: ?>
                <table style="width:100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                  <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; color: #4b5563;">
                      <th style="padding: 10px;">ID</th>
                      <th style="padding: 10px;">Objet</th>
                      <th style="padding: 10px;">Client</th>
                      <th style="padding: 10px;">Catégorie</th>
                      <th style="padding: 10px;">Note de Résolution</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($resolvedHistory as $t): ?>
                      <tr style="border-bottom: 1px solid #f3f4f6; color:#1e2a3a;">
                        <td style="padding: 12px 10px; font-weight:600;">#T-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td style="padding: 12px 10px;"><strong><?= htmlspecialchars($t['titre']); ?></strong></td>
                        <td style="padding: 12px 10px;"><?= htmlspecialchars($t['client_nom']); ?></td>
                        <td style="padding: 12px 10px;"><span class="tcat <?= $catClasses[$t['categorie'] ?? 'Logiciel'] ?? 'cat-sw'; ?>"><?= htmlspecialchars($t['categorie'] ?? 'Logiciel'); ?></span></td>
                        <td style="padding: 12px 10px; color:#1D4ED8; font-style:italic;"><?= htmlspecialchars($t['note_resolution'] ?? 'Aucune note fournie'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // 1. COMMUTATION DE VUES SANS REFRESH
    function switchView(viewName, el) {
        document.querySelectorAll('.sbi').forEach(item => item.classList.remove('on'));
        el.classList.add('on');
        document.querySelectorAll('.view-section').forEach(sec => sec.classList.remove('active'));
        document.getElementById('view-' + viewName).classList.add('active');
        
        const title = document.getElementById('topbar-title');
        title.textContent = viewName === 'resolved' ? "Vos interventions résolues" : "Mes tickets assignés";
    }

    // 2. RÉCUPÉRATION AJAX DES INFOS D'UN TICKET AU CLIC
    async function selectTicket(el) {
      document.querySelectorAll('.trow').forEach(r => r.classList.remove('sel'));
      el.classList.add('sel');

      const ticketId = el.getAttribute('data-id');

      try {
        const response = await fetch(`/projet/ticket/api/ticket/details?id=${ticketId}`);
        const ticket = await response.json();

        if (response.ok) {
          document.getElementById('det-id').textContent = '#T-' + String(ticket.id).padStart(4, '0');
          document.getElementById('det-meta').textContent = 'Soumis par ' + ticket.client_nom;
          document.getElementById('det-ai-text').innerHTML = `Catégorie détectée : <strong>${ticket.categorie}</strong>. Confiance calculée : <strong>${ticket.score_ia ?? 92}%</strong>.`;
          document.getElementById('det-title').textContent = ticket.titre;
          document.getElementById('det-desc').innerHTML = ticket.description.replace(/\n/g, "<br>");
          document.getElementById('det-client-row').textContent = ticket.client_nom;
          document.getElementById('det-cat-row').textContent = ticket.categorie;
          document.getElementById('det-prio-row').textContent = ticket.priorite;
          document.getElementById('note-resolution').value = ""; // Clear notes area
          document.getElementById('detail-pane').style.display = 'block';
        }
      } catch (err) {
        console.error("Erreur d'import des détails du ticket:", err);
      }
    }

    // 3. ENREGISTREMENT DE LA RÉSOLUTION D'UN TICKET
    document.getElementById('btn-resolve').addEventListener('click', async () => {
      const activeRow = document.querySelector('.trow.sel');
      if (!activeRow) return;

      const ticketId = activeRow.getAttribute('data-id');
      const note = document.getElementById('note-resolution').value;

      if (!note) {
          alert("Une note de résolution est obligatoire pour informer l'utilisateur.");
          return;
      }

      try {
        const response = await fetch('/projet/ticket/api/ticket/resolve', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: ticketId, resolution: note })
        });

        if (response.ok) {
          alert("L'incident a été résolu. Un e-mail SMTP automatique a été envoyé à l'employé.");
          location.reload(); 
        } else {
          alert("Erreur de communication lors de la clôture.");
        }
      } catch (err) {
        alert("Impossible de joindre la base de données.");
      }
    });

    // 4. ACTION : METTRE EN ATTENTE
    async function setAsPending() {
        const activeRow = document.querySelector('.trow.sel');
        if (!activeRow) return;
        const ticketId = activeRow.getAttribute('data-id');
        
        try {
            const response = await fetch('/projet/ticket/api/ticket/update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: ticketId, statut: 'En attente' })
            });
            if (response.ok) {
                alert("Statut mis à jour : En attente.");
                location.reload();
            }
        } catch(e) {
            console.error(e);
        }
    }

    // 5. NOTIFICATIONS IN-APP & EFFETS SMTP POUR LE TECHNICIEN
    function toggleNotifications() {
        const box = document.getElementById('notif-box');
        box.style.display = box.style.display === 'block' ? 'none' : 'block';
    }

    async function checkNotifications() {
        try {
            const response = await fetch('/projet/ticket/api/notifications/unread');
            const result = await response.json();
            
            if (response.ok && result.data) {
                const list = document.getElementById('notif-list');
                const bellIcon = document.getElementById('bell-icon');
                
                if (result.data.length > 0) {
                    bellIcon.style.color = '#E24B4A';
                    list.innerHTML = '';
                    
                    result.data.forEach(n => {
                        const div = document.createElement('div');
                        div.className = 'notif-item';
                        div.innerHTML = `
                            <div><strong>Routage IA :</strong> ${n.message}</div>
                            <div class="notif-warn">
                              <i class="ti ti-mail"></i> Un mail d'assignation SMTP a été poussé vers votre boîte.
                            </div>
                        `;
                        list.appendChild(div);
                    });
                } else {
                    bellIcon.style.color = '#6b7280';
                    list.innerHTML = '<div class="notif-item" style="text-align:center;color:#9ca3af">Aucune alerte récente</div>';
                }
            }
        } catch (err) {
            console.error("Erreur notifications technicien:", err);
        }
    }

    async function markAllNotificationsRead() {
        try {
            await fetch('/projet/ticket/api/notifications/mark-read', { method: 'POST' });
            toggleNotifications();
            checkNotifications();
        } catch(e) {
            console.error(e);
        }
    }

    // Démarrage & Polling toutes les 10 secondes
    checkNotifications();
    setInterval(checkNotifications, 10000);
  </script>
</body>
</html>