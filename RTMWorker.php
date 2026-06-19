<?php
// worker.php
// Script autonome destiné à tourner en tâche de fond (CLI) sur ton serveur Linux/XAMPP.
// Lancement : php worker.php

require_once __DIR__ . '/config/database.php'; 
require_once __DIR__ . '/config/mail.php'; 
require_once __DIR__ . '/vendor/autoload.php';

// On force l'affichage des erreurs pour le débogage dans la console
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🤖 Worker de messagerie HelpDesk AI démarré...\n";
echo "Presser CTRL+C pour l'arrêter.\n";

try {
    // Le worker vérifie si la colonne "mail_sent" existe pour suivre l'état de l'envoi
    $columns = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('mail_sent', $columns)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN mail_sent TINYINT(1) DEFAULT 0");
        echo "⚙️ Base de données mise à niveau : colonne 'mail_sent' ajoutée aux notifications.\n";
    }
} catch (Exception $e) {
    echo "⚠️ Erreur lors de l'auto-vérification BDD : " . $e->getMessage() . "\n";
    exit(1);
}

while (true) {
    try {
        // 1. On récupère les notifications non encore traitées par e-mail
        // On joint la table users pour connaître l'adresse mail cible
        $stmt = $pdo->prepare("
            SELECT n.id, n.message, u.email, u.nom, t.titre, t.priorite, t.id AS ticket_id
            FROM notifications n
            JOIN users u ON n.user_id = u.id
            LEFT JOIN tickets t ON n.ticket_id = t.id
            WHERE n.mail_sent = 0
            LIMIT 5
        ");
        $stmt->execute();
        $pendingAlerts = $stmt->fetchAll();

        if (count($pendingAlerts) > 0) {
            echo "\n✉️ [" . date('H:i:s') . "] Traitement de " . count($pendingAlerts) . " alerte(s) en file d'attente...\n";

            foreach ($pendingAlerts as $alert) {
                $destEmail = $alert['email'];
                $destNom = $alert['nom'];
                $ticketId = $alert['ticket_id'];
                $sujet = "🚨 Alerte Support HelpDesk — Incident #T-" . str_pad($ticketId, 4, '0', STR_PAD_LEFT);
                
                // Corps de l'email structuré en HTML clair et professionnel
                $corpsHtml = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px;'>
                        <h2 style='color: #185FA5; margin-top: 0;'>HelpDesk AI — Notification Automatique</h2>
                        <p>Bonjour <strong>{$destNom}</strong>,</p>
                        <p>Une action a été enregistrée sur votre espace technique concernant un ticket d'incident :</p>
                        <blockquote style='background: #f3f4f6; padding: 12px; border-left: 4px solid #378ADD; margin: 15px 0;'>
                            {$alert['message']}
                        </blockquote>
                        <div style='font-size: 12px; color: #6b7280; margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 10px;'>
                            Cet e-mail est généré automatiquement par l'Infrastructure Smart HelpDesk.<br>
                            Pour interagir avec le technicien, connectez-vous sur votre tableau de bord Astra.
                        </div>
                    </div>
                ";

                echo "📧 Envoi en cours à : {$destEmail} ... ";
                
                // Appel de ta fonction mailer via Gmail SMTP sécurisé
                $succes = envoyerEmail($destEmail, $destNom, $sujet, $corpsHtml);

                if ($succes) {
                    // On marque la notification comme traitée pour ne plus la renvoyer
                    $stmtUpdate = $pdo->prepare("UPDATE notifications SET mail_sent = 1 WHERE id = ?");
                    $stmtUpdate->execute([$alert['id']]);
                    echo "✅ Envoyé avec succès !\n";
                } else {
                    echo "❌ Échec de l'envoi (vérifier la config SMTP / logs apache).\n";
                    // Optionnel : on peut incrémenter un nombre d'essais pour éviter de bloquer la boucle
                }
            }
        }

    } catch (Exception $e) {
        echo "🔥 Erreur critique dans la boucle du Worker : " . $e->getMessage() . "\n";
    }

    // 2. Temporisation : On attend 5 secondes avant la prochaine lecture en base de données
    sleep(5);
}