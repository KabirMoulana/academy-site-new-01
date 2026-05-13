<?php
// ─── DATABASE CONFIGURATION ───────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change if needed
define('DB_PASS', '');           // XAMPP default is empty
define('DB_NAME', 'educore');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ─── CORS & JSON HEADERS ──────────────────────────────────
function setHeaders() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
}

// ─── RESPONSE HELPERS ─────────────────────────────────────
function success($data = [], $message = 'OK') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}
function error($message = 'Error', $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ─── AUTH HELPER ──────────────────────────────────────────
function requireAuth() {
    session_start();
    if (empty($_SESSION['userID'])) {
        error('Unauthorized. Please login.', 401);
    }
    return $_SESSION;
}

function getBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
