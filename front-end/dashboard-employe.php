<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Vérification de sécurité
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header("Location: /projet/ticket/login"); 
    exit();
}

require_once 'config/database.php';
$userId = $_SESSION['user_id'];

// =========================================================================
// 1. RÉCUPÉRATION DES MÉTRIQUES DE L'EMPLOYÉ CONNECTÉ
// =========================================================================

// Tickets ouverts (statut 'Nouveau')
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE client_id = ? AND statut = 'Nouveau'");
$stmt->execute([$userId]);
$ticketsOuverts = $stmt->fetchColumn();

// Tickets en cours (statut 'En cours' ou 'En attente')
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE client_id = ? AND statut IN ('En cours', 'En attente')");
$stmt->execute([$userId]);
$ticketsEnCours = $stmt->fetchColumn();

// Tickets résolus (statut 'Résolu')
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE client_id = ? AND statut = 'Résolu'");
$stmt->execute([$userId]);
$ticketsResolus = $stmt->fetchColumn();

// =========================================================================
// 2. RÉCUPÉRATION DE TOUS LES TICKETS DE L'EMPLOYÉ
// =========================================================================
$stmt = $pdo->prepare("
    SELECT t.*, u.nom AS tech_nom 
    FROM tickets t 
    LEFT JOIN users u ON t.technicien_id = u.id 
    WHERE t.client_id = ? 
    ORDER BY t.id DESC
");
$stmt->execute([$userId]);
$tousMesTickets = $stmt->fetchAll();

// Correspondances graphiques CSS
$statusClasses = [
    'Nouveau' => 'st-open',
    'En cours' => 'st-prog',
    'En attente' => 'st-prog',
    'Résolu' => 'st-done'
];

$catClasses = [
    'Réseau' => 'cat-net',
    'Matériel' => 'cat-hw',
    'Logiciel' => 'cat-sw',
    'Accès' => 'cat-acc',
    'Email' => 'cat-em'
];
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
    .sb { width: 210px; background: #1e2a3a; padding: 1.5rem 1rem; flex-shrink: 0; display: flex; flex-direction: column; }
    .sb-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
    .sb-icon { width: 30px; height: 30px; background: #378ADD; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
    .sb-icon i { color: #fff; font-size: 16px; }
    .sb-name { color: #fff; font-weight: 600; font-size: 15px; }
    .sb-sec { font-size: 11px; color: #378ADD; text-transform: uppercase; letter-spacing: .06em; margin: 1.25rem 0 .4rem; padding-left: 8px; }
    .sbi { display: flex; align-items: center; gap: 8px; padding: 9px 8px; border-radius: 8px; color: #85B7EB; margin-bottom: 2px; cursor: pointer; font-size: 14px; transition: background .15s; text-decoration: none; }
    .sbi:hover { background: rgba(55,138,221,.12); color: #fff; }
    .sbi.on { background: rgba(55,138,221,.2); color: #fff; }
    .sbi i { font-size: 17px; }

    /* Main */
    .main { flex: 1; background: #f4f5f7; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.75rem; background: #fff; border-bottom: 1px solid #e5e7eb; flex-shrink: 0; }
    .tb-title { font-size: 17px; font-weight: 600; color: #1e2a3a; }
    .tb-right { display: flex; align-items: center; gap: 12px; }
    .av { width: 32px; height: 32px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #185FA5; }
    
    /* Views & Body */
    .body { padding: 1.5rem 1.75rem; flex: 1; overflow-y: auto; }
    .view-section { display: none; }
    .view-section.active { display: block; }

    /* Metrics */
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.5rem; }
    .mc { background: #fff; border-radius: 10px; padding: .875rem 1rem; border: 1px solid #e5e7eb; }
    .mc-l { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .mc-v { font-size: 24px; font-weight: 600; color: #1e2a3a; }
    .mc-s { font-size: 11px; margin-top: 4px; }

    /* Grid layout with forms and details */
    .row2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 14px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; margin-bottom: 1.5rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }

    /* Ticket Rows */
    .trow { display: flex; align-items: center; gap: 8px; padding: 10px 8px; border-bottom: 1px solid #f3f4f6; cursor: pointer; border-radius: 6px; transition: background .12s; }
    .trow:hover { background: #f9fafb; }
    .trow:last-child { border-bottom: none; }
    .tid { font-size: 12px; color: #9ca3af; width: 54px; flex-shrink: 0; }
    .tdesc { font-size: 13px; color: #1e2a3a; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tcat { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; font-weight: 500; }
    .cat-net { background: #E6F1FB; color: #185FA5; }
    .cat-hw  { background: #FAEEDA; color: #854F0B; }
    .cat-sw  { background: #EEEDFE; color: #534AB7; }
    .cat-acc { background: #E1F5EE; color: #0F6E56; }
    .cat-em  { background: #FCEBEB; color: #A32D2D; }
    .tst { font-size: 11px; padding: 2px 8px; border-radius: 20px; flex-shrink: 0; font-weight: 500; }
    .st-open { background: #FFF0F0; color: #C53030; }
    .st-prog { background: #E6F1FB; color: #185FA5; }
    .st-done { background: #EBFBEE; color: #2B8A3E; }

    /* Modal / details panel */
    .detail-container { background: #f9fafb; border-radius: 8px; padding: 1rem; border: 1px solid #e5e7eb; }
    .res-box { background: #EBFBEE; border: 1px solid #A3E635; border-radius: 8px; padding: .75rem; margin-top: .75rem; }
    .res-title { font-size: 12px; font-weight: 600; color: #235A2D; display: flex; align-items: center; gap: 5px; }
    .res-text { font-size: 12px; color: #1D4ED8; margin-top: 4px; font-style: italic; }

    /* Form Fields */
    .flabel { font-size: 12px; color: #6b7280; margin-bottom: 5px; display: block; }
    .fg { margin-bottom: .875rem; }
    input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; outline: none; transition: border .15s; font-family: inherit; }
    input:focus, select:focus, textarea:focus { border-color: #378ADD; }
    .ai-hint { display: flex; align-items: center; gap: 6px; background: #E6F1FB; border-radius: 8px; padding: 8px 10px; font-size: 12px; color: #185FA5; margin-bottom: .875rem; }
    .sbtn { width: 100%; padding: 10px; background: #1e2a3a; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; }
    .sbtn:hover { background: #378ADD; }

    /* Notifications popup styling */
    .notif-bubble { display: none; position: absolute; top: 55px; right: 20px; width: 320px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); z-index: 100; max-height: 400px; overflow-y: auto; }
    .notif-header { padding: 10px 15px; border-bottom: 1px solid #f3f4f6; font-size: 13px; font-weight: 600; color: #1e2a3a; display: flex; justify-content: space-between; }
    .notif-item { padding: 12px 15px; border-bottom: 1px solid #f3f4f6; font-size: 12px; color: #4b5563; line-height: 1.4; }
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
      <div class="sb-sec">Menu</div>
      <div class="sbi on" onclick="switchView('dashboard', this)"><i class="ti ti-layout-dashboard"></i>Tableau de bord</div>
      <div class="sbi" onclick="switchView('tickets', this)"><i class="ti ti-ticket"></i>Mes tickets</div>
      
      <div class="sb-sec">Compte</div>
      <div class="sbi" onclick="toggleNotifications()"><i class="ti ti-bell"></i>Notifications</div>
      <a href="/projet/ticket/logout" class="sbi" style="color: #E24B4A; margin-top: auto;">
        <i class="ti ti-logout"></i>Déconnexion
      </a>
    </div>

    <div class="main">
      <div class="topbar">
        <span class="tb-title">Bonjour, <?= htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur'); ?> 👋</span>
        <div class="tb-right">
          <div style="position:relative">
            <i class="ti ti-bell" id="bell-icon" style="font-size:18px;color:#6b7280;cursor:pointer;" onclick="toggleNotifications()"></i>
            <!-- Bulle de Notifications -->
            <div class="notif-bubble" id="notif-box">
              <div class="notif-header">
                <span>Dernières alertes</span>
                <span style="color:#378ADD;cursor:pointer;" onclick="markAllAsRead()">Tout marquer lu</span>
              </div>
              <div id="notif-list">
                <div class="notif-item" style="text-align:center;color:#9ca3af">Aucune nouvelle notification</div>
              </div>
            </div>
          </div>
          <div class="av"><?= strtoupper(substr($_SESSION['nom'] ?? 'U', 0, 2)); ?></div>
          <span style="font-size:13px;color:#1e2a3a"><?= htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur'); ?></span>
        </div>
      </div>

      <div class="body">
        <!-- VUE 1 : DASHBOARD PRINCIPAL -->
        <div id="view-dashboard" class="view-section active">
          <div class="metrics">
            <div class="mc">
              <div class="mc-l">Tickets ouverts</div>
              <div class="mc-v"><?= $ticketsOuverts; ?></div>
              <div class="mc-s" style="color:#C53030">Non traités</div>
            </div>
            <div class="mc">
              <div class="mc-l">En cours</div>
              <div class="mc-v"><?= $ticketsEnCours; ?></div>
              <div class="mc-s" style="color:#185FA5">Pris en charge</div>
            </div>
            <div class="mc">
              <div class="mc-l">Résolus</div>
              <div class="mc-v"><?= $ticketsResolus; ?></div>
              <div class="mc-s" style="color:#2B8A3E">Clôturés</div>
            </div>
            <div class="mc">
              <div class="mc-l">Temps de réponse</div>
              <div class="mc-v">Automatique</div>
              <div class="mc-s" style="color:#6b7280">IA de routage actif</div>
            </div>
          </div>

          <div class="row2">
            <div class="card">
              <div class="ch">Dépôts récents</div>
              <div style="max-height: 380px; overflow-y: auto;">
                <?php if (empty($tousMesTickets)): ?>
                  <div style="text-align: center; padding: 20px; color: #6b7280;">Vous n'avez pas de ticket actif.</div>
                <?php else: ?>
                  <?php 
                  $recents = array_slice($tousMesTickets, 0, 5);
                  foreach ($recents as $ticket): 
                      $catName = $ticket['categorie'] ?? 'Analyse...';
                      $catClass = $catClasses[$catName] ?? 'cat-sw'; 
                      $stName = $ticket['statut'] ?? 'Nouveau';
                      $stClass = $statusClasses[$stName] ?? 'st-open';
                  ?>
                    <div class="trow" onclick="viewTicketDetails(<?= $ticket['id']; ?>)">
                      <span class="tid">#T-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></span>
                      <span class="tdesc"><strong><?= htmlspecialchars($ticket['titre']); ?></strong></span>
                      <span class="tcat <?= $catClass; ?>"><?= htmlspecialchars($catName); ?></span>
                      <span class="tst <?= $stClass; ?>"><?= htmlspecialchars($stName); ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div class="card">
              <div class="ch">Créer un nouveau ticket</div>
              <div class="fg">
                <label class="flabel" for="tobj">Objet succinct de la panne</label>
                <input type="text" id="tobj" placeholder="Ex: Impossible d'accéder au WiFi">
              </div>
              <div class="fg">
                <label class="flabel" for="tdesc2">Description précise</label>
                <textarea id="tdesc2" rows="4" placeholder="Fournissez le plus de détails possibles pour aider l'IA..."></textarea>
              </div>
              <div class="fg">
                <label class="flabel" for="tprio">Degré de priorité déclaré</label>
                <select id="tprio">
                  <option value="Faible">Faible (Aucun blocage direct)</option>
                  <option value="Normale" selected>Normale (Gêne de travail)</option>
                  <option value="Haute">Haute (Poste de travail inutilisable)</option>
                  <option value="Urgente">Urgente (Service critique KO)</option>
                </select>
              </div>
              <div class="ai-hint"><i class="ti ti-cpu" style="font-size:14px"></i>L'IA va automatiquement classifier le ticket et choisir l'ingénieur qualifié.</div>
              <button class="sbtn" id="btn-submit">Soumettre le ticket</button>
            </div>
          </div>
        </div>

        <!-- VUE 2 : ARCHIVES / TOUS LES TICKETS AVEC DE TAIL -->
        <div id="view-tickets" class="view-section">
          <div class="row2">
            <div class="card">
              <div class="ch">Historique complet de vos requêtes</div>
              <div style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($tousMesTickets)): ?>
                  <div style="text-align: center; padding: 20px; color: #6b7280;">Historique vide.</div>
                <?php else: ?>
                  <?php foreach ($tousMesTickets as $ticket): 
                      $catName = $ticket['categorie'] ?? 'Analyse...';
                      $catClass = $catClasses[$catName] ?? 'cat-sw'; 
                      $stName = $ticket['statut'] ?? 'Nouveau';
                      $stClass = $statusClasses[$stName] ?? 'st-open';
                  ?>
                    <div class="trow" onclick="viewTicketDetails(<?= $ticket['id']; ?>)">
                      <span class="tid">#T-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></span>
                      <span class="tdesc"><?= htmlspecialchars($ticket['titre']); ?></span>
                      <span class="tcat <?= $catClass; ?>"><?= htmlspecialchars($catName); ?></span>
                      <span class="tst <?= $stClass; ?>"><?= htmlspecialchars($stName); ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div class="card">
              <div class="ch">Détails de l'incident sélectionné</div>
              <div id="ticket-detail-panel" style="display:none;">
                <div class="detail-container">
                  <h3 style="color:#1e2a3a; font-size:15px; margin-bottom: 5px;" id="det-lbl-id">#T-0000</h3>
                  <div style="font-size:12px; color:#6b7280; margin-bottom: 12px;">
                    Date de soumission : <span id="det-lbl-date">En cours d'affichage</span>
                  </div>
                  <div style="font-size:14px; font-weight:600; color:#1e2a3a; margin-bottom:8px;" id="det-lbl-titre">Titre du ticket</div>
                  <div style="font-size:13px; color:#4b5563; line-height: 1.5; margin-bottom:15px;" id="det-lbl-desc">Description de la panne...</div>
                  
                  <div style="font-size:12px; border-top:1px solid #e5e7eb; padding-top:10px;">
                    <p style="margin-bottom:4px;">Technicien assigné : <strong id="det-lbl-tech">En cours</strong></p>
                    <p style="margin-bottom:4px;">Statut actuel : <strong id="det-lbl-statut">Nouveau</strong></p>
                    <p>Catégorisation IA : <span class="tcat cat-net" id="det-lbl-cat">Réseau</span> (Fiabilité : <span id="det-lbl-score">0</span>%)</p>
                  </div>

                  <div class="res-box" id="det-lbl-res-box" style="display:none;">
                    <div class="res-title"><i class="ti ti-check"></i> Solution technique apportée :</div>
                    <div class="res-text" id="det-lbl-resolution">Détails de résolution...</div>
                  </div>
                </div>
              </div>
              <div id="ticket-detail-placeholder" style="text-align:center; padding: 40px 10px; color:#9ca3af; font-size:13px;">
                Cliquez sur un ticket pour faire apparaître les informations de prise en charge et d'assignation en temps réel.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // 1. COMMUTATEUR DE VUES SANS RECHARGEMENT
    function switchView(viewName, el) {
        document.querySelectorAll('.sbi').forEach(item => item.classList.remove('on'));
        el.classList.add('on');
        document.querySelectorAll('.view-section').forEach(sec => sec.classList.remove('active'));
        document.getElementById('view-' + viewName).classList.add('active');
    }

    // 2. ENVOI ASYNCHRONE DE NOUVEAUX TICKETS
    document.getElementById('btn-submit').addEventListener('click', async (e) => {
        e.preventDefault();
        const btn = e.target;
        const obj = document.getElementById('tobj').value;
        const desc = document.getElementById('tdesc2').value;
        const prio = document.getElementById('tprio').value;

        if (!obj || !desc) {
            alert("Veuillez renseigner un objet ainsi qu'une description détaillée.");
            return;
        }

        btn.disabled = true;
        btn.innerText = "Classification IA en cours...";

        try {
            const response = await fetch('/projet/ticket/api/ticket/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ titre: obj, description: desc, priorite: prio })
            });

            const res = await response.json();
            if (response.ok) {
                alert("Votre ticket a été enregistré et classifié ! Vérifiez vos notifications de messagerie.");
                location.reload(); 
            } else {
                alert("Erreur réseau : " + res.message);
                btn.disabled = false;
                btn.innerText = "Soumettre le ticket";
            }
        } catch (err) {
            alert("Impossible d'initier la liaison avec le serveur HelpDesk.");
            btn.disabled = false;
            btn.innerText = "Soumettre le ticket";
        }
    });

    // 3. AFFICHAGE DES DÉTAILS D'UN TICKET AU CLIC
    async function viewTicketDetails(ticketId) {
        switchView('tickets', document.querySelector('[onclick="switchView(\'tickets\', this)"]'));
        const placeholder = document.getElementById('ticket-detail-placeholder');
        const panel = document.getElementById('ticket-detail-panel');

        placeholder.style.display = 'none';
        panel.style.display = 'block';

        try {
            // Utilisation de la route déjà existante de détails
            const response = await fetch(`/projet/ticket/api/ticket/details?id=${ticketId}`);
            const ticket = await response.json();

            if (response.ok) {
                document.getElementById('det-lbl-id').textContent = '#T-' + String(ticket.id).padStart(4, '0');
                document.getElementById('det-lbl-titre').textContent = ticket.titre;
                document.getElementById('det-lbl-desc').textContent = ticket.description;
                document.getElementById('det-lbl-tech').textContent = ticket.technicien_nom ? ticket.technicien_nom : 'En cours d\'affectation...';
                document.getElementById('det-lbl-statut').textContent = ticket.statut;
                document.getElementById('det-lbl-cat').textContent = ticket.categorie;
                document.getElementById('det-lbl-score').textContent = ticket.score_ia ? ticket.score_ia : '90';

                const resBox = document.getElementById('det-lbl-res-box');
                if (ticket.statut === 'Résolu' && ticket.note_resolution) {
                    resBox.style.display = 'block';
                    document.getElementById('det-lbl-resolution').textContent = ticket.note_resolution;
                } else {
                    resBox.style.display = 'none';
                }
            }
        } catch (err) {
            console.error("Erreur d'affichage des détails du ticket:", err);
        }
    }

    // 4. NOTIFICATIONS POPUP & ALERTES SMTP INTÉGRÉES
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
                    bellIcon.style.color = '#E24B4A'; // Devient rouge
                    list.innerHTML = '';
                    
                    result.data.forEach(n => {
                        const div = document.createElement('div');
                        div.className = 'notif-item';
                        div.innerHTML = `
                            <div>${n.message}</div>
                            <div class="notif-warn">
                              <i class="ti ti-mail"></i> Un mail d'alerte SMTP a été expédié à votre adresse.
                            </div>
                        `;
                        list.appendChild(div);
                    });
                } else {
                    bellIcon.style.color = '#6b7280';
                    list.innerHTML = '<div class="notif-item" style="text-align:center;color:#9ca3af">Aucune nouvelle notification</div>';
                }
            }
        } catch (err) {
            console.error("Problème de lecture des notifications in-app:", err);
        }
    }

    // Polling toutes les 10 secondes
    checkNotifications();
    setInterval(checkNotifications, 10000);
  </script>
</body>
</html>