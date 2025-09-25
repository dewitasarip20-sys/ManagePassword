<?php
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt_secret = 'h8rE!qN@72^3klM90#xWzA$Lp4bT&cY*fVsD1zUgPjKXeGo';

function verifyToken() {
    global $jwt_secret;

    // Ambil Authorization header
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header missing']);
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid Authorization header format']);
        exit;
    }

    $jwt = $matches[1];

    try {
        $decoded = JWT::decode($jwt, new Key($jwt_secret, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }
}
