<?php
$ch = curl_init('http://127.0.0.1:5000/predict');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['description' => 'test']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "Erreur cURL : " . curl_error($ch);
} else {
    echo "Réponse reçue : " . $response;
}
curl_close($ch);
?>
