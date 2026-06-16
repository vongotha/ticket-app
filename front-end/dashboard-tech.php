<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technicien') {
    header("Location: /projet/ticket/login"); 
    exit();
}

$techId = $_SESSION['user_id'];

// =========================================================================
// 1. REQUÊTES SQL : Métriques du technicien connecté
// =========================================================================

// Tickets Actifs assignés (Non résolus)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE technicien_id = ? AND statut != 'Résolu'");
$stmt->execute([$techId]);
$ticketsAssignes = $stmt->fetchColumn();

// Tickets Urgents assignés
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE technicien_id = ? AND statut != 'Résolu' AND priorite = 'Urgente'");
$stmt->execute([$techId]);
$ticketsUrgents = $stmt->fetchColumn();

// Tickets Résolus par lui ce mois-ci
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE technicien_id = ? AND statut = 'Résolu'");
$stmt->execute([$techId]);
$ticketsResolus = $stmt->fetchColumn();


// =========================================================================
// 2. REQUÊTE SQL : File d'attente (Tickets non résolus assignés à ce tech)
// =========================================================================
$stmt = $pdo->prepare("SELECT t.*, u.nom AS client_nom FROM tickets t LEFT JOIN users u ON t.client_id = u.id WHERE t.technicien_id = ? AND t.statut != 'Résolu' ORDER BY CASE WHEN t.priorite = 'Urgente' THEN 1 WHEN t.priorite = 'Haute' THEN 2 ELSE 3 END, t.id DESC");
$stmt->execute([$techId]);
$queueTickets = $stmt->fetchAll();

// Mappings CSS
$prioClasses = ['Urgente' => 'pr-urg', 'Haute' => 'pr-haut', 'Normale' => 'pr-norm', 'Faible' => 'pr-norm'];
$catClasses  = ['Réseau' => 'cat-net', 'Matériel' => 'cat-hw', 'Logiciel' => 'cat-sw', 'Accès' => 'cat-acc'];

// On pré-charge le premier ticket pour ne pas avoir un panneau droit vide
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
    /* Conserve ton bloc de styles CSS à l'identique ici */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f7; display: flex; min-height: 100vh; }
    .app { display: flex; width: 100%; min-height: 100vh; }
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
    .main { flex: 1; background: #f4f5f7; overflow-y: auto; }
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.75rem; background: #fff; border-bottom: 1px solid #e5e7eb; }
    .tb-title { font-size: 17px; font-weight: 600; color: #1e2a3a; }
    .av { width: 32px; height: 32px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #185FA5; }
    .body { padding: 1.5rem 1.75rem; }
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.5rem; }
    .mc { background: #fff; border-radius: 10px; padding: .875rem 1rem; border: 1px solid #e5e7eb; }
    .mc-l { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .mc-v { font-size: 24px; font-weight: 600; color: #1e2a3a; }
    .mc-s { font-size: 11px; margin-top: 4px; }
    .row2 { display: grid; grid-template-columns: 1fr 1.5fr; gap: 14px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; }
    .trow { display: flex; align-items: center; gap: 8px; padding: 8px 6px; border-bottom: 1px solid #f3f4f6; cursor: pointer; border-radius: 8px; transition: background .12s; }
    .trow:hover { background: #f9fafb; }
    .trow.sel { background: #E6F1FB; border-color: transparent; }
    .trow:last-child { border-bottom: none; }
    .tid { font-size: 12px; color: #9ca3af; width: 54px; flex-shrink: 0; }
    .tdesc { font-size: 13px; color: #1e2a3a; flex: 1; }
    .tcat { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .cat-net { background: #E6F1FB; color: #185FA5; }
    .cat-hw  { background: #FFF0F0; color: #C53030; }
    .cat-sw  { background: #EBFBEE; color: #2B8A3E; }
    .cat-acc { background: #F3F0FF; color: #6741D9; }
    .tpr { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; }
    .pr-urg  { background: #FCEBEB; color: #A32D2D; }
    .pr-haut { background: #FAEEDA; color: #854F0B; }
    .pr-norm { background: #f3f4f6; color: #6b7280; }
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
  </style>
</head>
<body>
  <div class="app">
    <!-- BARRE LATÉRALE -->
    <div class="sb">
      <div class="sb-logo">
        <div class="sb-icon"><i class="ti ti-headset"></i></div>
        <span class="sb-name">HelpDesk AI</span>
      </div>
      <div class="sb-sec">Technicien</div>
      <div class="sbi on"><i class="ti ti-layout-dashboard"></i>Mes tickets<span class="badge-sb"><?= $ticketsAssignes; ?></span></div>
      <div class="sbi"><i class="ti ti-check"></i>Résolus</div>
      <div class="sb-sec">Compte</div>
      <div class="sbi"><i class="ti ti-bell"></i>Notifications</div>
      <div class="sbi"><i class="ti ti-user"></i>Mon profil</div>
      <a href="/projet/ticket/logout" style="text-decoration: none;">
        <div class="sbi" style="color: #E24B4A;"><i class="ti ti-logout"></i>Déconnexion</div>
      </a>
    </div>

    <!-- MAIN -->
    <div class="main">
      <div class="topbar">
        <span class="tb-title">Mes tickets assignés</span>
        <div style="display:flex;align-items:center;gap:12px">
          <i class="ti ti-bell" style="font-size:18px;color:#6b7280"></i>
          <div class="av"><?= strtoupper(substr($_SESSION['nom'] ?? 'T', 0, 2)); ?></div>
          <span style="font-size:13px;color:#1e2a3a"><?= htmlspecialchars($_SESSION['nom'] ?? 'Technicien'); ?></span>
        </div>
      </div>

      <div class="body">
        <!-- MÉTRIQUES -->
        <div class="metrics">
          <div class="mc"><div class="mc-l">Assignés</div><div class="mc-v"><?= $ticketsAssignes; ?></div><div class="mc-s" style="color:#6b7280">Actifs</div></div>
          <div class="mc"><div class="mc-l">Urgents</div><div class="mc-v"><?= $ticketsUrgents; ?></div><div class="mc-s" style="color:#A32D2D">À traiter</div></div>
          <div class="mc"><div class="mc-l">Résolus</div><div class="mc-v"><?= $ticketsResolus; ?></div><div class="mc-s" style="color:#0F6E56">Fermés</div></div>
          <div class="mc"><div class="mc-l">Délai moyen</div><div class="mc-v">3h20</div><div class="mc-s" style="color:#6b7280">Résolution</div></div>
        </div>

        <div class="row2">
          <!-- FILE D'ATTENTE (GAUCHE) -->
          <div class="card">
            <div class="ch">File d'attente</div>
            <?php if (empty($queueTickets)): ?>
              <div style="text-align:center; padding:20px; color:#6b7280;">Aucun ticket en attente.</div>
            <?php else: ?>
              <?php foreach ($queueTickets as $index => $t): ?>
                <div class="trow <?= $index === 0 ? 'sel' : ''; ?>" data-id="<?= $t['id']; ?>" onclick="selectTicket(this)">
                  <span class="tid">#T-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></span>
                  <span class="tdesc"><?= htmlspecialchars($t['titre']); ?></span>
                  <span class="tcat <?= $catClasses[$t['categorie'] ?? 'Logiciel'] ?? 'cat-sw'; ?>"><?= htmlspecialchars($t['categorie'] ?? 'Logiciel'); ?></span>
                  <span class="tpr <?= $prioClasses[$t['priorite'] ?? 'Normale'] ?? 'pr-norm'; ?>"><?= htmlspecialchars($t['priorite'] ?? 'Normale'); ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- DETAIL PANEL (DROITE) -->
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
              <div class="ai-box-title"><i class="ti ti-cpu" style="font-size:14px"></i>Analyse IA</div>
              <div class="ai-box-text" id="det-ai-text">
                Catégorie détectée : <strong><?= htmlspecialchars($firstTicket['categorie'] ?? 'Non catégorisé'); ?></strong>. Confiance : <strong><?= $firstTicket['score_ia'] ?? '90'; ?>%</strong>.
              </div>
            </div>

            <div style="margin-bottom:1rem">
              <div id="det-title" style="font-size:13px;font-weight:600;color:#1e2a3a;margin-bottom:.5rem"><?= htmlspecialchars($firstTicket['titre'] ?? ''); ?></div>
              <div id="det-desc" style="font-size:13px;color:#6b7280;line-height:1.6"><?= nl2br(htmlspecialchars($firstTicket['description'] ?? '')); ?></div>
            </div>

            <div class="info-block">
              <div style="font-size:12px;color:#6b7280;margin-bottom:8px;font-weight:500">Informations du ticket</div>
              <div class="info-row"><span class="info-label">Demandeur</span><span class="info-val" id="det-client-row"><?= htmlspecialchars($firstTicket['client_nom'] ?? 'Inconnu'); ?></span></div>
              <div class="info-row"><span class="info-label">Catégorie IA</span><span class="info-val" id="det-cat-row"><?= htmlspecialchars($firstTicket['categorie'] ?? 'Logiciel'); ?></span></div>
              <div class="info-row"><span class="info-label">Priorité</span><span class="info-val" id="det-prio-row"><?= htmlspecialchars($firstTicket['priorite'] ?? 'Normale'); ?></span></div>
            </div>

            <div style="font-size:12px;color:#6b7280;margin-bottom:6px">Note de résolution</div>
            <textarea id="note-resolution" rows="2" placeholder="Décrivez la solution apportée..."></textarea>
            
            <div class="action-row">
              <button class="abtn"><i class="ti ti-clock" style="font-size:14px"></i>En attente</button>
              <button class="abtn abtn-prim" id="btn-resolve"><i class="ti ti-check" style="font-size:14px"></i>Marquer résolu</button>
            </div>
          </div>
          
          <!-- Si la file d'attente est vide -->
          <?php if (!$firstTicket): ?>
            <div class="card" style="display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:14px;">
               Sélectionnez un ticket pour afficher ses détails.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    // 1. Changement dynamique de ticket au clic (AJAX)
    async function selectTicket(el) {
      document.querySelectorAll('.trow').forEach(r => r.classList.remove('sel'));
      el.classList.add('sel');

      const ticketId = el.getAttribute('data-id');

      try {
        const response = await fetch(`/projet/ticket/api/ticket/details?id=${ticketId}`);
        const ticket = await response.json();

        if (response.ok) {
          // Injection des données dans le panneau de droite
          document.getElementById('det-id').textContent = '#T-' + String(ticket.id).padStart(4, '0');
          document.getElementById('det-meta').textContent = 'Soumis par ' + ticket.client_nom;
          document.getElementById('det-ai-text').innerHTML = `Catégorie détectée : <strong>${ticket.categorie}</strong>. Confiance : <strong>${ticket.score_ia ?? 92}%</strong>.`;
          document.getElementById('det-title').textContent = ticket.titre;
          document.getElementById('det-desc').textContent = ticket.description;
          document.getElementById('det-client-row').textContent = ticket.client_nom;
          document.getElementById('det-cat-row').textContent = ticket.categorie;
          document.getElementById('det-prio-row').textContent = ticket.priorite;
          document.getElementById('detail-pane').style.display = 'block';
        }
      } catch (err) {
        console.error("Erreur de chargement du ticket:", err);
      }
    }

    // 2. Traitement de la résolution
    document.getElementById('btn-resolve').addEventListener('click', async () => {
      const activeRow = document.querySelector('.trow.sel');
      if (!activeRow) return;

      const ticketId = activeRow.getAttribute('data-id');
      const note = document.getElementById('note-resolution').value;

      if(!note) {
          alert("Veuillez saisir une note de résolution avant de fermer le ticket.");
          return;
      }

      try {
        const response = await fetch('/projet/ticket/api/ticket/resolve', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: ticketId, resolution: note })
        });

        if (response.ok) {
          alert("Ticket clôturé avec succès !");
          location.reload(); // Recharge pour vider la file et mettre à jour les métriques
        }
      } catch (err) {
        alert("Erreur lors de la clôture du ticket.");
      }
    });
  </script>
</body>
</html>