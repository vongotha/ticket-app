<?php
// Inclure ta config PDO
require_once 'config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    // =========================================================================
    // SÉCURITÉ COMPATIBILITÉ : Ajout des colonnes et mise à niveau de l'ENUM
    // =========================================================================
    $columns = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('categorie', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN categorie VARCHAR(100) DEFAULT NULL");
    }
    if (!in_array('score_ia', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN score_ia INT DEFAULT NULL");
    }
    if (!in_array('note_resolution', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN note_resolution TEXT DEFAULT NULL");
    }
    if (!in_array('technicien_id', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN technicien_id INT DEFAULT NULL");
    }

    // ICI : On force l'ENUM à accepter toutes les variantes pour éviter le crash 1265
    $pdo->exec("ALTER TABLE tickets MODIFY COLUMN priorite ENUM('Faible', 'Normale', 'Haute', 'Urgent', 'Urgente') DEFAULT 'Normale'");
    $pdo->exec("ALTER TABLE tickets MODIFY COLUMN statut ENUM('Nouveau', 'En cours', 'En attente', 'Résolu') DEFAULT 'Nouveau'");

    // =========================================================================
    // 1. NETTOYAGE DES TABLES
    // =========================================================================
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("TRUNCATE TABLE notifications");
    $pdo->exec("TRUNCATE TABLE tickets");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // =========================================================================
    // 2. INJECTION DES UTILISATEURS
    // =========================================================================
    $password = password_hash('password123', PASSWORD_BCRYPT);
    $stmtUser = $pdo->prepare("INSERT INTO users (id, nom, email, password, role) VALUES (?, ?, ?, ?, ?)");

    $users = [
        [1, 'Admin Astra', 'admin@astra.com', $password, 'admin'],
        [2, 'Karim Mansouri', 'karim@astra.com', $password, 'technicien'],
        [3, 'Sara Lamine', 'sara@astra.com', $password, 'technicien'],
        [4, 'Amine Mekki', 'amine@astra.com', $password, 'technicien'],
        [5, 'Nadia Brahimi', 'nadia@astra.com', $password, 'technicien'],
        [6, 'Employé Test', 'user@astra.com', $password, 'employe']
    ];

    foreach ($users as $user) {
        $stmtUser->execute($user);
    }

    // =========================================================================
    // 3. INJECTION DES TICKETS (Avec 'Urgent' nettoyé)
    // =========================================================================
    $stmtTicket = $pdo->prepare("
        INSERT INTO tickets (titre, description, categorie, priorite, statut, score_ia, client_id, technicien_id, note_resolution) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $tickets = [
        [
            'VPN ne fonctionne plus depuis mise à jour', 
            'Depuis ce matin après la mise à jour Windows, impossible de monter le tunnel VPN.', 
            'Réseau', 'Urgent', 'En cours', 94, 6, 2, null
        ],
        [
            'Lenteurs WiFi open space', 
            'Déconnexions intempestives de la borne wifi principale de l\'étage.', 
            'Réseau', 'Haute', 'En cours', 88, 6, 2, null
        ],
        [
            'Logiciel comptabilité crash au démarrage', 
            'Le logiciel Sage Compta se ferme tout seul lors du chargement du module fiscal.', 
            'Logiciel', 'Haute', 'Nouveau', 91, 6, 3, null
        ],
        [
            'Compte AD bloqué après tentatives', 
            'Message de compte verrouillé suite à une erreur de saisie de mot de passe.', 
            'Accès', 'Urgent', 'Nouveau', 95, 6, 4, null
        ],
        [
            'Imprimante bureau RH hors ligne', 
            'L\'imprimante refuse de sortir les documents, le spooler reste bloqué.', 
            'Matériel', 'Normale', 'En cours', 78, 6, 4, null
        ],
        [
            'Problème accès boîte Outlook', 
            'Les emails externes mettaient plusieurs heures à arriver dans la boîte de réception.', 
            'Logiciel', 'Normale', 'Résolu', 85, 6, 3, 'Cache Outlook vidé, profil de messagerie recréé.'
        ]
    ];

    foreach ($tickets as $ticket) {
        $stmtTicket->execute($ticket);
    }

    echo json_encode([
        "status" => "success", 
        "message" => "Base de données mise à jour et réinitialisée sans aucune erreur !"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Erreur lors du seeding : " . $e->getMessage()
    ]);
}