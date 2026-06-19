<?php
// Inclure ta config PDO
require_once 'config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    // =========================================================================
    // 1. SÉCURITÉ COMPATIBILITÉ : Ajout des colonnes pour l'IA et les TICKETS
    // =========================================================================
    $columnsTickets = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('categorie', $columnsTickets)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN categorie VARCHAR(100) DEFAULT NULL");
    }
    if (!in_array('score_ia', $columnsTickets)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN score_ia INT DEFAULT NULL");
    }
    if (!in_array('note_resolution', $columnsTickets)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN note_resolution TEXT DEFAULT NULL");
    }
    if (!in_array('technicien_id', $columnsTickets)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN technicien_id INT DEFAULT NULL");
    }

    $pdo->exec("ALTER TABLE tickets MODIFY COLUMN priorite ENUM('Faible', 'Normale', 'Haute', 'Urgent', 'Urgente') DEFAULT 'Normale'");
    $pdo->exec("ALTER TABLE tickets MODIFY COLUMN statut ENUM('Nouveau', 'En cours', 'En attente', 'Résolu') DEFAULT 'Nouveau'");

    // =========================================================================
    // 2. SÉCURITÉ COMPATIBILITÉ : Ajout de la colonne SPECIALITE pour les USERS
    // =========================================================================
    $columnsUsers = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('specialite', $columnsUsers)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN specialite VARCHAR(50) DEFAULT NULL");
    }

    // =========================================================================
    // 3. NETTOYAGE DES TABLES
    // =========================================================================
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("TRUNCATE TABLE notifications");
    $pdo->exec("TRUNCATE TABLE tickets");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // =========================================================================
    // 4. INJECTION DES UTILISATEURS (AVEC LEURS SPÉCIALITÉS !)
    // =========================================================================
    $password = password_hash('password123', PASSWORD_BCRYPT);
    // On prépare l'insertion avec la 6ème colonne : specialite
    $stmtUser = $pdo->prepare("INSERT INTO users (id, nom, email, password, role, specialite) VALUES (?, ?, ?, ?, ?, ?)");

    $users = [
        [1, 'Admin Astra', 'admin@astra.com', $password, 'admin', null],
        [2, 'Karim Mansouri', 'simon1mukeba@icloud.com', $password, 'technicien', 'Réseau'],
        [3, 'Sara Lamine', 'eliekiyimbi6@gmail.com', $password, 'technicien', 'Logiciel'],
        [4, 'Amine Mekki', 'benjaminmulangu44@gmail.com', $password, 'technicien', 'Accès'],
        [5, 'Nadia Brahimi', 'clautyda123@gmail.com', $password, 'technicien', 'Matériel'],
        [6, 'Tech Support', 'mpongojohvani1@gmail.com', $password, 'technicien', 'Email'], // Ajouté au cas où l'IA dit "Email"
        [7, 'Employé Test', 'gradidibu412@gmail.com', $password, 'employe', null]
    ];

    foreach ($users as $user) {
        $stmtUser->execute($user);
    }

    // =========================================================================
    // 5. INJECTION DES TICKETS 
    // =========================================================================
    $stmtTicket = $pdo->prepare("
        INSERT INTO tickets (titre, description, categorie, priorite, statut, score_ia, client_id, technicien_id, note_resolution) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $tickets = [
        [
            'Imprimante bureau RH hors ligne', 
            'L\'imprimante refuse de sortir les documents, le spooler reste bloqué.', 
            'Matériel', 'Normale', 'En cours', 78, 7, 5, null
        ], 
        [
            'Problème accès boîte Outlook', 
            'Les emails externes mettaient plusieurs heures à arriver dans la boîte de réception.', 
            'Logiciel', 'Normale', 'Résolu', 85, 7, 3, 'Cache Outlook vidé, profil de messagerie recréé.'
        ]
    ];

    foreach ($tickets as $ticket) {
        $stmtTicket->execute($ticket);
    } 

    echo json_encode([
        "status" => "success", 
        "message" => "Base de données mise à jour et réinitialisée SANS ERREUR !"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Erreur lors du seeding : " . $e->getMessage()
    ]);
}