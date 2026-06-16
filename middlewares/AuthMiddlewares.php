<?php

class AuthMiddleware {
    
    // 1. Vérifie si l'utilisateur est simplement connecté
    public static function checkAuth() {
        // Démarrer la session si elle ne l'est pas déjà
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            echo json_encode(["message" => "Session : Nouvelle Session. Vous venez de commencer une nouvelle session."]);
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Unauthorized
            echo json_encode(["error" => "Accès refusé : Vous devez être connecté."]);
            exit; // Stoppe net l'exécution du script
        }
    }

    // 2. Vérifie si l'utilisateur possède un rôle spécifique (ex: 'technicien' ou 'admin')
    public static function checkRole($requiredRole) {
        // On s'assure d'abord qu'il est connecté
        self::checkAuth();

        if ($_SESSION['role'] !== $requiredRole) {
            http_response_code(403); // Forbidden (Connecté, mais pas les bons droits)
            echo json_encode(["error" => "Accès interdit : Privilèges insuffisants."]);
            exit; 
        }
    }
}
