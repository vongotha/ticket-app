// 1. Inscription (Sign Up)
function handleSignUp($pdo) {
    $data = json_get_input();
    
    if (empty($data['email']) || empty($data['password']) || empty($data['nom'])) {
        http_response_code(400);
        echo json_encode(["error" => "Données incomplètes."]);
        return;
    }

    // Hachage du mot de passe
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, 'employe')");
        $stmt->execute([$data['nom'], $data['email'], $hashedPassword]);
        
        http_response_code(21); // Created
        echo json_encode(["message" => "Utilisateur créé avec succès !"]);
    } catch (\PDOException $e) {
        http_response_code(400);
        echo json_encode(["error" => "L'email existe déjà ou une erreur est survenue."]);
    }
}

// 2. Connexion (Login)
function handleLogin($pdo) {
    $data = json_get_input();

    if (empty($data['email']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Email et mot de passe requis."]);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    // Vérification du mot de passe
    if ($user && password_verify($data['password'], $user['password'])) {
        // Préparation du chemin pour l'Auth (génération de session ou token)
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        http_response_code(200);
        echo json_encode([
            "message" => "Connexion réussie.",
            "user" => [
                "id" => $user['id'],
                "nom" => $user['nom'],
                "role" => $user['role']
            ]
        ]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(["error" => "Identifiants incorrects."]);
    }
}

// Fonction utilitaire pour lire le JSON envoyé par le Frontend (Vue/React)
function json_get_input() {
    return json_decode(file_get_contents('php://input'), true);
}
