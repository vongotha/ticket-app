<?php
// Inclure ta config BDD
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $client_id = $_SESSION['user_id'] ?? 6; // ID de l'employé connecté (6 = user de test)

    // -------------------------------------------------------------------------
    // APPEL À L'IA PYTHON VIA CURL
    // -------------------------------------------------------------------------
    $url_api_ia = 'http://localhost:5000/predict';
    
    // On prépare les données à envoyer en JSON
    $data_to_send = json_encode(['description' => $description]);

    $ch = curl_init($url_api_ia);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_send);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_to_send)
    ]);
    
    // Définir un timeout court (ex: 3 secondes max) pour ne pas bloquer l'utilisateur si l'IA a un problème
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Valeurs par défaut si l'IA est éteinte ou plante
    $categorie_predite = 'À déterminer';
    $score_ia = null;

    // Si l'API Python a répondu correctement (Code HTTP 200)
    if ($http_code === 200 && $response) {
        $result_ia = json_decode($response, true);
        if (isset($result_ia['status']) && $result_ia['status'] === 'success') {
            $categorie_predite = $result_ia['categorie'];
            $score_ia = $result_ia['score_ia'];
        }
    }

    // -------------------------------------------------------------------------
    // ENREGISTREMENT EN BASE DE DONNÉES
    // -------------------------------------------------------------------------
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tickets (titre, description, categorie, score_ia, priorite, statut, client_id) 
            VALUES (?, ?, ?, ?, 'Normale', 'Nouveau', ?)
        ");
        
        $stmt->execute([
            $titre, 
            $description, 
            $categorie_predite, // Insère par exemple 'Réseau' ou 'Logiciel' automatiquement !
            $score_ia,          // Insère le pourcentage calculé par l'IA
            $client_id
        ]);

        // Redirection vers le dashboard avec un message de succès
        header('Location: front-end/dashboard-admin.php?msg=ticket_cree');
        exit;

    } catch (Exception $e) {
        die("Erreur bdd : " . $e->getMessage());
    }
}
?>