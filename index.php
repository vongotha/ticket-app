<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// On démarre la session globalement pour savoir qui est connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'controllers/AuthController.php';

// Récupérer l'URL et la méthode HTTP
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Nettoyer le chemin selon ton dossier dans htdocs
$basePath = '/projet/ticket'; 
$route = str_replace($basePath, '', $requestUri);

if ($route === '' || $route === '/') {
    $route = '/';
}

switch ($route) {

    // =========================================================================
    // 1. ROUTES WEB (Affichage des pages visuelles depuis le dossier front-end)
    // =========================================================================
    
    case '/':
    case '/login':
        // Si l'utilisateur est déjà connecté, on le redirige directement vers son dashboard
        if (isset($_SESSION['role'])) {
            header("Location: " . $basePath . "/dashboard-" . $_SESSION['role']);
            exit();
        }
        // Sinon, on charge la page de connexion (Pas de header JSON ici !)
        include 'front-end/login.php';
        break;

    case '/dashboard-employe':
        include 'front-end/dashboard-employe.php';
        break;

    case '/dashboard-technicien':
        include 'front-end/dashboard-tech.php';
        break;

    case '/dashboard-admin':
        include 'front-end/dashboard-admin.php';
        break;


    // =========================================================================
    // 2. ROUTES API (Traitement des données - Renvoient exclusivement du JSON)
    // =========================================================================
    
    case '/api/signup':
        header("Content-Type: application/json");
        if ($requestMethod === 'POST') {
            AuthController::signup($pdo);
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée. Utilisez POST."]);
        }
        break;

    case '/api/login':
        header("Content-Type: application/json");
        if ($requestMethod === 'POST') {
            AuthController::login($pdo);
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée. Utilisez POST."]);
        }
        break;

    case '/api/ticket/create':
        header("Content-Type: application/json");
        if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $clientId = $_SESSION['user_id'] ?? 1; // Sécurité de repli
            
            $stmt = $pdo->prepare("INSERT INTO tickets (titre, description, client_id, provenance) VALUES (?, ?, ?, 'web')");
            $stmt->execute([$data['titre'], $data['description'], $clientId]);
            echo json_encode(["message" => "Ticket créé avec succès"]);
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée."]);
        }
        break;

    case '/api/notifications/unread':
        header("Content-Type: application/json");
        $userId = $_SESSION['user_id'] ?? 0; 
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND est_lu = 0");
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll();
        echo json_encode(["data" => $notifs]);
        break;

    case '/api/notifications/mark-read':
        header("Content-Type: application/json");
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE notifications SET est_lu = 1 WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(["status" => "ok"]);
        break;

    case '/api/ticket/analyze':
        header("Content-Type: application/json");
        http_response_code(202); 
        echo json_encode(["message" => "Ticket reçu. Analyse IA en cours..."]);
        break;

    default:
        // Si aucune route ne correspond (Web ou API)
        http_response_code(404);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Route [ " . $route . " ] non trouvée."]);
        break;
}