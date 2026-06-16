<?php
// Inclure ta config PDO
require_once 'config/database.php';

try {
    // 1. Désactivation des contraintes pour permettre le truncate
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Vider les tables
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("TRUNCATE TABLE notifications");
    $pdo->exec("TRUNCATE TABLE tickets");
    
    // Note: on laisse les catégories intactes pour ne pas perdre la config IA
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 3. Préparer les nouveaux utilisateurs
    // Mot de passe pour tous : "password123" (haché via BCRYPT)
    $password = password_hash('password123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, ?)");

    $users = [
        ['Admin Astra', 'admin@astra.com', $password, 'admin'],
        ['Technicien Support', 'tech@astra.com', $password, 'technicien'],
        ['Employé Test', 'user@astra.com', $password, 'employe']
    ];

    foreach ($users as $user) {
        $stmt->execute($user);
    }

    echo json_encode(["status" => "success", "message" => "Base de données réinitialisée avec les comptes de test."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>