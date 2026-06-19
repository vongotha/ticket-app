<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/mailer.php';

header('Content-Type: application/json');

// Récupération des données POST
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !isset($data['resolution'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Données manquantes"]);
    exit();
}

try {
    $ticketId = $data['id'];
    $resolutionNote = $data['resolution'];

    // 1. Mise à jour du ticket
    $stmt = $pdo->prepare("UPDATE tickets SET statut = 'Résolu', note_resolution = ? WHERE id = ?");
    $stmt->execute([$resolutionNote, $ticketId]);

    // 2. Récupération des infos du ticket et du client
    $stmtInfo = $pdo->prepare("SELECT t.titre, u.email, u.nom FROM tickets t JOIN users u ON t.client_id = u.id WHERE t.id = ?");
    $stmtInfo->execute([$ticketId]);
    $ticket = $stmtInfo->fetch();

    if ($ticket) {
        // 3. Insertion de la notification in-app (pour l'employé)
        $msgNotif = "Votre ticket #T-" . str_pad($ticketId, 4, '0', STR_PAD_LEFT) . " a été résolu.";
        $pdo->prepare("INSERT INTO notifications (user_id, message, date_creation) SELECT client_id, ?, NOW() FROM tickets WHERE id = ?")
            ->execute([$msgNotif, $ticketId]);

        // 4. Envoi de l'e-mail automatique (via mailer.php)
        $emailBody = "
            <h2>Notification HelpDesk</h2>
            <p>Bonjour " . htmlspecialchars($ticket['nom']) . ",</p>
            <p>Votre demande <strong>" . htmlspecialchars($ticket['titre']) . "</strong> a été résolue par notre équipe technique.</p>
            <div style='background: #f4f5f7; padding: 15px; border-radius: 8px;'>
                <p><strong>Note du technicien :</strong><br>" . nl2br(htmlspecialchars($resolutionNote)) . "</p>
            </div>
            <p>Merci d'avoir fait confiance à l'assistance Astra.</p>
        ";

        envoyerEmail($ticket['email'], $ticket['nom'], "Ticket #T-{$ticketId} Résolu", $emailBody);
    }

    echo json_encode(["status" => "success", "message" => "Ticket résolu et employé notifié."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}