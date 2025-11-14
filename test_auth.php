<?php

// Test script for OM Pay authentication endpoints

echo "=== Test des endpoints d'authentification OM Pay ===\n\n";

// Configuration
$baseUrl = 'http://localhost:8000/api';

// Test 1: Étape 1 - Login (envoi SMS)
echo "Test 1: POST /api/auth/login\n";
$loginData = [
    'telephone' => '782917770' // Utilisateur de test
];

$ch = curl_init($baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $loginHttpCode\n";
echo "Response: $loginResponse\n\n";

$loginResult = json_decode($loginResponse, true);
$sessionId = $loginResult['data']['session_id'] ?? null;

if ($sessionId) {
    // Test 2: Étape 2 - Vérification code secret
    echo "Test 2: POST /api/auth/verify-code-secret\n";
    echo "Session ID: $sessionId\n";

    // Récupérer le code secret de test depuis les logs
    echo "Vérifiez les logs Laravel pour le code SMS généré\n";
    echo "Code secret de test par défaut: 1234\n";

    $verifyData = [
        'telephone' => '782917770',
        'code_secret' => '1234', // Code secret de test
        'session_id' => $sessionId
    ];

    $ch = curl_init($baseUrl . '/auth/verify-code-secret');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verifyData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $verifyResponse = curl_exec($ch);
    $verifyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Status: $verifyHttpCode\n";
    echo "Response: $verifyResponse\n\n";

    $verifyResult = json_decode($verifyResponse, true);
    $token = $verifyResult['data']['token'] ?? null;

    if ($token) {
        // Test 3: Test avec le token
        echo "Test 3: GET /api/v1/auth/profile (avec token)\n";

        $ch = curl_init($baseUrl . '/v1/auth/profile');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);

        $profileResponse = curl_exec($ch);
        $profileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "Status: $profileHttpCode\n";
        echo "Response: $profileResponse\n\n";
    }
}

echo "=== Fin des tests ===\n";