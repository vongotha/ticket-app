<?php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORRECTION : On démarre la session au tout début du cycle de vie de la requête
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

// Récupérer l'URL et la méthode HTTP
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Nettoyer le chemin (ex: /votre-projet/api/login -> /login)
$basePath = '/projet/ticket'; // À ajuster selon le nom de ton dossier dans htdocs
$route = str_replace($basePath, '', $requestUri);

// Si l'utilisateur appelle juste http://localhost/projet/ticket/, la route sera vide ou '/'
if ($route === '' || $route === '/') {
    $route = '/';
}

switch ($route) {

	case '/':
        echo json_encode(["message" => "Bienvenue sur l'API de l'AI-Driven Helpdesk (Astra Techn)"]);
        break;

    // ROUTES PUBLIQUES SANS AUTHENTIFICATION NECESSAIRE ~ 
    case '/signup':
        if ($requestMethod === 'POST') {
            AuthController::signup($pdo);
        } else {
            // Méthode non autorisée
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée. Utilisez POST."]);
        }
        break;

    case '/login':
        if ($requestMethod === 'POST') {
            AuthController::login($pdo);
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée. Utilisez POST."]);
        }
        break;

    // ROUTES PRIVEE NECESSITE UNE AUTHENTIFICATION 

    case '/ticket/analyze':
        // Route pour l'intégration future de l'IA
        // On renvoie un code 202 (Accepted) car le traitement IA/RTM peut prendre du temps
        http_response_code(202); 
        echo json_encode(["message" => "Ticket reçu. Analyse IA en cours de traitement..."]);
        break;

    case '/admin/dashboard':
        // Route non encore développée (Exemple pour le code 501)
        http_response_code(501); // Not Implemented
        echo json_encode(["message" => "Fonctionnalité non implémentée pour le moment."]);
        break;

	case '/ticket/create':
        // Simule l'arrivée d'un mail
        $stmt = $pdo->prepare("INSERT INTO tickets (titre, description, client_id, provenance) VALUES (?, ?, 1, 'web')");
        $stmt->execute(['Test via Postman', 'Ceci est un ticket de test']);
        echo json_encode(["message" => "Ticket créé avec succès"]);
        break;
    case '/notifications/unread':
        // Récupérer l'ID de l'utilisateur connecté (depuis la session ou token)
        $userId = $_SESSION['user_id']; 
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND est_lu = 0");
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll();
        
        echo json_encode(["data" => $notifs]);
    break;

    case '/notifications/mark-read':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE notifications SET est_lu = 1 WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(["status" => "ok"]);
    break;

    default:
        // Route inconnue
        http_response_code(404); // Not Found
        echo json_encode(["error" => "Route non trouvée."]);
        break;
}
