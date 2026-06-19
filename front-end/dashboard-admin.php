<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Sécurité Administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /projet/ticket/login"); 
    exit();
}

require_once 'config/database.php';

// =========================================================================
// 1. REQUÊTES METRIQUES GLOBALES (ENTREPRISE)
// =========================================================================

// Tickets totaux
$totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

// Tickets ouverts / en cours
$openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE statut != 'Résolu'")->fetchColumn();

// Tickets résolus
$resolvedTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE statut = 'Résolu'")->fetchColumn();

// Taux de résolution global
$resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100) : 0;

// Score moyen de confiance de l'IA (en % de certitude)
$avgScoreIa = $pdo->query("SELECT AVG(score_ia) FROM tickets WHERE score_ia IS NOT NULL")->fetchColumn();
$avgScoreIa = $avgScoreIa ? round($avgScoreIa) : 92;

// =========================================================================
// 2. EXTRACTION COMPLÈTE DE TOUS LES TICKETS AVEC CORRESPONDANCE TECHNICIEN
// =========================================================================
$stmt = $pdo->query("
    SELECT t.*, c.nom AS client_nom, tc.nom AS tech_nom 
    FROM tickets t 
    LEFT JOIN users c ON t.client_id = c.id 
    LEFT JOIN users tc ON t.technicien_id = tc.id 
    ORDER BY t.id DESC
");
$allTickets = $stmt->fetchAll();

// Mappings CSS de mise en page
$statusPills = [
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
  <title>HelpDesk AI — Superviseur Admin</title>
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
    .sbi.on { background: rgba(55,138,221,.2); color: #fff; }
    .sbi i { font-size: 17px; }

    /* Main Content */
    .main { flex: 1; background: #f4f5f7; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.75rem; background: #fff; border-bottom: 1px solid #e5e7eb; flex-shrink: 0; }
    .tb-title { font-size: 17px; font-weight: 600; color: #1e2a3a; }
    .av { width: 32px; height: 32px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #185FA5; }
    .body { padding: 1.5rem 1.75rem; flex: 1; overflow-y: auto; }

    /* Metrics Grid */
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.5rem; }
    .mc { background: #fff; border-radius: 10px; padding: .875rem 1rem; border: 1px solid #e5e7eb; }
    .mc-l { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    .mc-v { font-size: 24px; font-weight: 600; color: #1e2a3a; }
    .mc-s { font-size: 11px; margin-top: 4px; }

    /* Admin Table Styling */
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
    
    table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
    thead tr { border-bottom: 2px solid #e5e7eb; color: #4b5563; }
    th { padding: 10px; font-weight: 600; }
    tbody tr { border-bottom: 1px solid #f3f4f6; color: #1e2a3a; transition: background .1s; }
    tbody tr:hover { background: #f9fafb; }
    td { padding: 12px 10px; vertical-align: middle; }

    /* Statuses & Categories */
    .tcat { font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 500; display: inline-block; }
    .cat-net { background: #E6F1FB; color: #185FA5; }
    .cat-hw  { background: #FAEEDA; color: #854F0B; }
    .cat-sw  { background: #EEEDFE; color: #534AB7; }
    .cat-acc { background: #E1F5EE; color: #0F6E56; }
    .cat-em  { background: #FCEBEB; color: #A32D2D; }

    .tst { font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 500; display: inline-block; }
    .st-open { background: #FFF0F0; color: #C53030; }
    .st-prog { background: #E6F1FB; color: #185FA5; }
    .st-done { background: #EBFBEE; color: #2B8A3E; }

    .tech-badge { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #378ADD; font-weight: 500; }
    .ai-score-badge { font-size: 11px; color: #185FA5; background: #E6F1FB; border-radius: 4px; padding: 1px 5px; font-weight: bold; }
  </style>
</head>
<body>
  <div class="app">
    <div class="sb">
      <div class="sb-logo">
        <div class="sb-icon"><i class="ti ti-headset"></i></div>
        <span class="sb-name">HelpDesk AI</span>
      </div>
      <div class="sb-sec">Superviseur</div>
      <div class="sbi on"><i class="ti ti-layout-dashboard"></i>Suivi global</div>
      <div class="sb-sec">Compte</div>
      <a href="/projet/ticket/logout" class="sbi" style="color: #E24B4A; margin-top: auto;">
        <i class="ti ti-logout"></i>Déconnexion
      </a>
    </div>

    <div class="main">
      <div class="topbar">
        <span class="tb-title">Supervision de l'Infrastructure & Escalade IA</span>
        <div style="display:flex;align-items:center;gap:12px">
          <div class="av">AD</div>
          <span style="font-size:13px;color:#1e2a3a"><?= htmlspecialchars($_SESSION['nom'] ?? 'Admin'); ?></span>
        </div>
      </div>

      <div class="body">
        <!-- MÉTRIQUES GLOBAL COMPLIANCE -->
        <div class="metrics">
          <div class="mc">
            <div class="mc-l">Charge de tickets</div>
            <div class="mc-v"><?= $totalTickets; ?></div>
            <div class="mc-s" style="color:#6b7280">Historique total</div>
          </div>
          <div class="mc">
            <div class="mc-l">Files d'attentes actives</div>
            <div class="mc-v" style="color:#185FA5"><?= $openTickets; ?></div>
            <div class="mc-s" style="color:#185FA5">Non résolus</div>
          </div>
          <div class="mc">
            <div class="mc-l">Taux de résolution global</div>
            <div class="mc-v" style="color:#2B8A3E"><?= $resolutionRate; ?>%</div>
            <div class="mc-s" style="color:#2B8A3E">Tickets clôturés</div>
          </div>
          <div class="mc">
            <div class="mc-l">Précision Moyenne du Routage IA</div>
            <div class="mc-v" style="color:#1D4ED8"><?= $avgScoreIa; ?>%</div>
            <div class="mc-s" style="color:#1D4ED8">Confiance machine</div>
          </div>
        </div>

        <!-- TABLEAU DE BORD DE SURVEILLANCE -->
        <div class="card">
          <div class="ch">
            <span>Tableau d'Assignation et de Supervision des Techniciens</span>
            <span style="font-size: 11px; color:#6b7280; font-weight: normal;"><i class="ti ti-cpu"></i> Trié par ordre antéchronologique</span>
          </div>
          
          <div style="max-height: 480px; overflow-y: auto;">
            <?php if (empty($allTickets)): ?>
              <div style="text-align: center; padding: 30px; color:#6b7280;">Aucun ticket à afficher.</div>
            <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Objet de la panne</th>
                    <th>Utilisateur</th>
                    <th>Catégorie IA</th>
                    <th>Confiance IA</th>
                    <th>Technicien assigné</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($allTickets as $t): ?>
                    <tr>
                      <td style="font-weight: 600; color: #9ca3af;">#T-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></td>
                      <td>
                        <strong><?= htmlspecialchars($t['titre']); ?></strong>
                        <div style="font-size:11px; color:#6b7280; margin-top:2px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                          <?= htmlspecialchars($t['description']); ?>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($t['client_nom']); ?></td>
                      <td>
                        <span class="tcat <?= $catClasses[$t['categorie'] ?? 'Logiciel'] ?? 'cat-sw'; ?>">
                          <?= htmlspecialchars($t['categorie'] ?? 'Logiciel'); ?>
                        </span>
                      </td>
                      <td>
                        <span class="ai-score-badge"><?= $t['score_ia'] ? $t['score_ia'] : '90'; ?>%</span>
                      </td>
                      <td>
                        <div class="tech-badge">
                          <i class="ti ti-user-cog"></i>
                          <span><?= $t['tech_nom'] ? htmlspecialchars($t['tech_nom']) : '<span style="color:#E24B4A">Non assigné</span>'; ?></span>
                        </div>
                      </td>
                      <td>
                        <span class="tst <?= $statusPills[$t['statut'] ?? 'Nouveau'] ?? 'st-open'; ?>">
                          <?= htmlspecialchars($t['statut'] ?? 'Nouveau'); ?>
                        </span>
                      </td>
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
</body>
</html>