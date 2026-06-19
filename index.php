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
            $ticketId = $data['id'] ?? 0;
            $resolutionNote = $data['resolution'] ?? 'Aucune note';
            
            // 1. Mise à jour du ticket
            $stmt = $pdo->prepare("UPDATE tickets SET statut = 'Résolu', note_resolution = ? WHERE id = ?");
            $stmt->execute([$resolutionNote, $ticketId]);
            
            // 2. Récupérer l'email de l'employé (le client)
            $stmtEmail = $pdo->prepare("SELECT u.email FROM users u JOIN tickets t ON u.id = t.client_id WHERE t.id = ?");
            $stmtEmail->execute([$ticketId]);
            $clientEmail = $stmtEmail->fetchColumn();
            
            // 3. Insérer dans la file d'attente pour informer l'employé
            if ($clientEmail) {
                $sujet = "Ticket #{$ticketId} Résolu";
                $message = "Bonjour, votre ticket a été résolu. Note du technicien : " . $resolutionNote;
                
                $stmtMail = $pdo->prepare("INSERT INTO mail_queue (ticket_id, destinataire_email, sujet, message) VALUES (?, ?, ?, ?)");
                $stmtMail->execute([$ticketId, $clientEmail, $sujet, $message]);
            }
            
            echo json_encode(["status" => "success", "message" => "Ticket clos et email de notification en file d'attente."]);
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée"]);
        }
        break;

    case '/api/ticket/create':
        header("Content-Type: application/json");
        if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $clientId = $_SESSION['user_id'] ?? 1;
            
            $titre = $data['titre'] ?? '';
            $description = $data['description'] ?? '';
            $priorite = $data['priorite'] ?? 'Normale';

            // ==========================================
            // 1. INTERROGER L'IA (Serveur Python)
            // ==========================================
            $url_api_ia = 'http://127.0.0.1:5000/predict'; // Utilise l'IP directement
            $data_to_send = json_encode(['description' => $description]);

            $ch = curl_init($url_api_ia);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_send);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5s pour laisser le temps au modèle

            error_log("[PHP-DEBUG] Envoi vers IA : " . $data_to_send);

            // On exécute la requête vers l'IA
            $response = curl_exec($ch);
            
            // On vérifie les erreurs réseau (ex: Python éteint)
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                http_response_code(500);
                echo json_encode(["message" => "Erreur de connexion à l'IA : " . $error_msg]);
                exit;
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Valeurs par défaut si l'IA ne répond pas proprement
            $categorie_predite = 'Logiciel'; 
            $score_ia = null;

            if ($http_code === 200 && $response) {
                $result_ia = json_decode($response, true);
                error_log("[PHP-DEBUG] Réponse reçue de l'IA : " . print_r($result_ia, true));

                if (isset($result_ia['status']) && $result_ia['status'] === 'success') {
                    $categorie_predite = $result_ia['categorie'];
                    $score_ia = $result_ia['score_ia'];
                }
            }

            // ==========================================
            // LOGIQUE BDD : ASSIGNATION & INSERTION
            // ==========================================
            try {
                // 2. LOGIQUE D'ASSIGNATION AUTOMATIQUE
                // Chercher un technicien avec la spécialité correspondant à la prédiction
                $stmtTech = $pdo->prepare("SELECT id FROM users WHERE role = 'technicien' AND specialite = ? LIMIT 1");
                $stmtTech->execute([$categorie_predite]);
                $technicienId = $stmtTech->fetchColumn();

                // Fallback: Si aucun technicien spécialisé n'est trouvé, on prend le premier dispo
                if (!$technicienId) {
                    $stmtFallback = $pdo->prepare("SELECT id FROM users WHERE role = 'technicien' LIMIT 1");
                    $stmtFallback->execute();
                    $technicienId = $stmtFallback->fetchColumn() ?: null;
                }

                // 3. INSERTION DU TICKET EN BASE
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (titre, description, client_id, technicien_id, provenance, categorie, score_ia, priorite, statut) 
                    VALUES (?, ?, ?, ?, 'web', ?, ?, ?, 'Nouveau')
                ");
                $stmt->execute([
                    $titre, 
                    $description, 
                    $clientId, 
                    $technicienId, 
                    $categorie_predite, 
                    $score_ia, 
                    $priorite
                ]);
                
                // Récupération de l'ID du ticket fraîchement créé
                $ticketId = $pdo->lastInsertId();

                // 4. CRÉATION DE LA NOTIFICATION WEB (IN-APP)
                if ($technicienId) {
                    $msgNotif = "🤖 Nouveau ticket #T-{$ticketId} assigné d'office par l'IA (Catégorie: {$categorie_predite}).";
                    // CORRECTION APPLIQUÉE : Ajout de ticket_id dans la requête et l'execute
                    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, ticket_id, message, est_lu) VALUES (?, ?, ?, 0)");
                    $stmtNotif->execute([$technicienId, $ticketId, $msgNotif]);
                    
                    // ... après $stmtNotif->execute([...]);

                    if ($technicienId) {
                        // 1. Récupérer l'email du technicien
                        $stmtEmail = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                        $stmtEmail->execute([$technicienId]);
                        $techEmail = $stmtEmail->fetchColumn();

                        if ($techEmail) {
                            // 2. Insérer dans la file d'attente (au lieu d'envoyer direct)
                            $sujet = "Nouveau Ticket #T-{$ticketId} - IA Assignation";
                            $contenu = "Bonjour, un nouveau ticket '{$titre}' a été détecté comme étant de catégorie '{$categorie_predite}'. Vous êtes assigné.";
                            
                            $stmtMail = $pdo->prepare("INSERT INTO mail_queue (ticket_id, destinataire_email, sujet, message) VALUES (?, ?, ?, ?)");
                            $stmtMail->execute([$ticketId, $techEmail, $sujet, $contenu]);
                        }
                    }
                }

                // Réponse finale positive au frontend JS
                echo json_encode(["status" => "success", "message" => "Ticket créé et assigné automatiquement."]);

            } catch (Exception $e) {
                // Intercepte toute erreur MySQL (comme la 1364) et la renvoie au frontend
                http_response_code(500);
                echo json_encode(["message" => "Erreur BDD : " . $e->getMessage()]);
            }
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée"]);
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