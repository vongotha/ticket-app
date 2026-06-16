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
    case '/api/ticket/details':
            header("Content-Type: application/json");
            $ticketId = $_GET['id'] ?? 0;
            
            // Récupérer le ticket combiné avec le nom de l'utilisateur qui l'a créé
            $stmt = $pdo->prepare("SELECT t.*, u.nom AS client_nom FROM tickets t LEFT JOIN users u ON t.client_id = u.id WHERE t.id = ?");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            if ($ticket) {
                echo json_encode($ticket);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Ticket introuvable"]);
            }
        break;

    case '/api/ticket/resolve':
            header("Content-Type: application/json");
            if ($requestMethod === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                // On passe le statut à 'Résolu' et on enregistre la note de fin
                $stmt = $pdo->prepare("UPDATE tickets SET statut = 'Résolu', note_resolution = ? WHERE id = ?");
                $stmt->execute([$data['resolution'], $data['id']]);
                
                echo json_encode(["status" => "success", "message" => "Ticket clos"]);
            } else {
                http_response_code(405);
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

    case '/logout':
    // 1. On vide toutes les variables de session
    $_SESSION = array();

    // 2. On détruit le cookie de session dans le navigateur
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // 3. On détruit la session sur le serveur
    session_destroy();

    // 4. On redirige proprement vers la ROUTE de login (sans .php)
    header("Location: /projet/ticket/login");
    exit();

    default:
        // Si aucune route ne correspond (Web ou API)
        http_response_code(404);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Route [ " . $route . " ] non trouvée."]);
        break;
}