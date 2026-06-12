<?php
header("Content-Type: application/json");
require_once 'config/database.php';

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


case '/signup':
        if ($requestMethod === 'POST') {
            handleSignUp($pdo);
        } else {
            // Méthode non autorisée
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée. Utilisez POST."]);
        }
        break;

    case '/login':
        if ($requestMethod === 'POST') {
            handleLogin($pdo);
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée. Utilisez POST."]);
        }
        break;

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

    default:
        // Route inconnue
        http_response_code(404); // Not Found
        echo json_encode(["error" => "Route non trouvée."]);
        break;
}
