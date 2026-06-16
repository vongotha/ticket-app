<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Sécurité : Si l'utilisateur n'est pas admin, redirection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /projet/ticket/login"); 
    exit();
}

// =========================================================================
// 1. REQUÊTES SQL : Métriques globales de l'entreprise
// =========================================================================

// Nombre total de tickets
$totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

// Tickets ouverts / en cours (non résolus)
$openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE statut != 'Résolu'")->fetchColumn();

// Tickets résolus
$resolvedTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE statut = 'Résolu'")->fetchColumn();

// Tickets non résolus et urgents
$urgentTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE statut != 'Résolu' AND priorite = 'Urgente'")->fetchColumn();

// Calcul automatique des taux
$resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100) : 0;

// Précision moyenne de l'IA (basée sur le score_ia en DB, ou 92% par défaut)
$avgIaScore = $pdo->query("SELECT AVG(score_ia) FROM tickets WHERE score_ia IS NOT NULL")->fetchColumn();
$avgIaScore = $avgIaScore ? round($avgIaScore) : 92;


// =========================================================================
// 2. REQUÊTE SQL : Répartition par catégorie (pour le graphique en barres)
// =========================================================================
$categories = ['Réseau' => 0, 'Logiciel' => 0, 'Matériel' => 0, 'Accès' => 0];
$catQuery = $pdo->query("SELECT categorie, COUNT(*) as count FROM tickets WHERE categorie IS NOT NULL GROUP BY categorie")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fusion avec notre tableau par défaut pour éviter les index manquants
foreach ($catQuery as $catName => $count) {
    if (array_key_exists($catName, $categories)) {
        $categories[$catName] = $count;
    }
}
// Trouve la valeur maximale pour calibrer la largeur CSS (width %) des barres
$maxCatCount = max(1, max($categories));


// =========================================================================
// 3. REQUÊTE SQL : Liste des techniciens et leur charge de travail
// =========================================================================
$techs = $pdo->query("
    SELECT u.nom, COUNT(t.id) AS active_tickets 
    FROM users u 
    LEFT JOIN tickets t ON u.id = t.technicien_id AND t.statut != 'Résolu' 
    WHERE u.role = 'technicien' 
    GROUP BY u.id 
    LIMIT 4
")->fetchAll();


// =========================================================================
// 4. REQUÊTE SQL : Les 4 tickets les plus récents du système
// =========================================================================
$recentTickets = $pdo->query("SELECT * FROM tickets ORDER BY id DESC LIMIT 4")->fetchAll();

// Mappings CSS pour les badges de couleur
$prioClasses = ['Urgente' => 'pr-urg', 'Haute' => 'pr-haut', 'Normale' => 'pr-norm', 'Faible' => 'pr-norm'];
$catClasses  = ['Réseau' => 'cat-net', 'Matériel' => 'cat-hw', 'Logiciel' => 'cat-sw', 'Accès' => 'cat-acc'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HelpDesk AI — Dashboard Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.7.0/dist/tabler-icons.min.css">
  <style>
    /* Conserve tes styles CSS d'origine intacts */
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
    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.125rem 1.25rem; }
    .ch { font-size: 14px; font-weight: 600; color: #1e2a3a; margin-bottom: 1rem; }
    .bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .bar-label { width: 80px; font-size: 12px; color: #6b7280; text-align: right; flex-shrink: 0; }
    .bar-bg { flex: 1; height: 9px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 4px; }
    .bar-val { font-size: 12px; color: #6b7280; min-width: 28px; text-align: right; }
    .urow { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .urow:last-child { border-bottom: none; }
    .uav { width: 30px; height: 30px; border-radius: 50%; background: #E6F1FB; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #185FA5; flex-shrink: 0; }
    .urole { font-size: 11px; color: #9ca3af; }
    .ust { font-size: 11px; padding: 2px 8px; border-radius: 20px; }
    .st-on { background: #E1F5EE; color: #0F6E56; }
    .st-off { background: #FAEEDA; color: #854F0B; }
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
      <div class="sbi"><i class="ti ti-ticket"></i>Tous les tickets<span class="badge-sb"><?= $openTickets; ?></span></div>
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
          <div class="av"><?= strtoupper(substr($_SESSION['nom'] ?? 'AD', 0, 2)); ?></div>
          <span style="font-size:13px;color:#1e2a3a"><?= htmlspecialchars($_SESSION['nom'] ?? 'Admin'); ?></span>
        </div>
      </div>

      <div class="body">
        <div class="metrics">
          <div class="mc">
            <div class="mc-l">Tickets totaux</div>
            <div class="mc-v"><?= $totalTickets; ?></div>
            <div class="mc-s" style="color:#6b7280">Historique complet</div>
          </div>
          <div class="mc">
            <div class="mc-l">Ouverts</div>
            <div class="mc-v"><?= $openTickets; ?></div>
            <div class="mc-s" style="color:#A32D2D"><?= $urgentTickets; ?> urgent<?= $urgentTickets > 1 ? 's' : ''; ?></div>
          </div>
          <div class="mc">
            <div class="mc-l">Résolus</div>
            <div class="mc-v"><?= $resolvedTickets; ?></div>
            <div class="mc-s" style="color:#0F6E56"><?= $resolutionRate; ?>% taux de clôture</div>
          </div>
          <div class="mc">
            <div class="mc-l">Précision IA</div>
            <div class="mc-v"><?= $avgIaScore; ?>%</div>
            <div class="mc-s" style="color:#185FA5">Catégorisation</div>
          </div>
        </div>

        <div class="row2">
          <div class="card">
            <div class="ch">Tickets par catégorie</div>
            <?php 
              // Couleurs spécifiques pour correspondre à ton design
              $barColors = ['Réseau' => '#378ADD', 'Logiciel' => '#7F77DD', 'Matériel' => '#EF9F27', 'Accès' => '#1D9E75'];
              foreach ($categories as $catName => $count): 
                $barWidth = round(($count / $maxCatCount) * 100);
                $color = $barColors[$catName] ?? '#378ADD';
            ?>
              <div class="bar-row">
                <span class="bar-label"><?= $catName; ?></span>
                <div class="bar-bg">
                  <div class="bar-fill" style="width:<?= $barWidth; ?>%; background:<?= $color; ?>"></div>
                </div>
                <span class="bar-val"><?= $count; ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <div class="ch">Techniciens actifs</div>
            <?php if (empty($techs)): ?>
              <div style="text-align:center; padding:20px; color:#6b7280; font-size:13px;">Aucun technicien enregistré.</div>
            <?php else: ?>
              <?php foreach ($techs as $t): 
                $isBusy = $t['active_tickets'] >= 3;
                $statusText = $isBusy ? 'Occupé' : 'Disponible';
                $statusClass = $isBusy ? 'st-off' : 'st-on';
              ?>
                <div class="urow">
                  <div class="uav"><?= strtoupper(substr($t['nom'], 0, 2)); ?></div>
                  <div style="flex:1">
                    <div style="font-size:13px;color:#1e2a3a;font-weight:500"><?= htmlspecialchars($t['nom']); ?></div>
                    <div class="urole">En charge de — <?= $t['active_tickets']; ?> ticket<?= $t['active_tickets'] > 1 ? 's' : ''; ?></div>
                  </div>
                  <span class="ust <?= $statusClass; ?>"><?= $statusText; ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="ch">Tickets récents (Toutes catégories)</div>
          <?php if (empty($recentTickets)): ?>
            <div style="text-align:center; padding:20px; color:#6b7280; font-size:13px;">Aucun ticket dans le système.</div>
          <?php else: ?>
            <?php foreach ($recentTickets as $ticket): 
              $cName = $ticket['categorie'] ?? 'Logiciel';
              $pName = $ticket['priorite'] ?? 'Normale';
            ?>
              <div class="tix-row">
                <span class="tid">#T-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></span>
                <span class="tdesc"><?= htmlspecialchars($ticket['titre']); ?></span>
                <span class="tcat <?= $catClasses[$cName] ?? 'cat-sw'; ?>"><?= htmlspecialchars($cName); ?></span>
                <span class="tpr <?= $prioClasses[$pName] ?? 'pr-norm'; ?>"><?= htmlspecialchars($pName); ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>