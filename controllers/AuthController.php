<?php
// controllers/AuthController.php
class AuthController {
    public static function login($pdo) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(["error" => "Email et mot de passe requis"]);
            return;
        }

        // 1. Chercher l'utilisateur par email
        $stmt = $pdo->prepare("SELECT id, nom, password, role FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        // 2. Vérifier si l'utilisateur existe ET si le hash correspond
        if ($user && password_verify($data['password'], $user['password'])) {

            // Connexion réussie, on renvoie les données pour le Frontend
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "user" => [
                    "id" => $user['id'],
                    "nom" => $user['nom'],
                    "role" => $user['role'] // Important pour que le front sache s'il affiche le dashboard tech ou employe
                ]
            ]);

            // Si le mot de passe est bon :
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(["error" => "Email ou mot de passe incorrect."]);
        }
    }


    public static function signup($pdo) {
        $data = json_decode(file_get_contents('php://input'), true);

        // 1. Validation basique
        if (empty($data['email']) || empty($data['password']) || empty($data['nom'])) {
            http_response_code(400);
            echo json_encode(["error" => "Champs manquants"]);
            return;
        }

        // 2. Hashage sécurisé
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // 3. Insertion avec Requête Préparée
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, 'employe')");
            $stmt->execute([$data['nom'], $data['email'], $hashedPassword]);
            
            http_response_code(201);
            echo json_encode(["message" => "Compte créé avec succès."]);
        } catch (PDOException $e) {
            // Erreur 23000 = violation de contrainte d'unicité (email déjà utilisé)
            if ($e->getCode() == 23000) {
                http_response_code(409);
                echo json_encode(["error" => "Cet email est déjà utilisé."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erreur serveur."]);
            }
        }
    }
}