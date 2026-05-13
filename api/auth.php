<?php
require_once '../config/db.php';
setHeaders();
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ─── LOGIN ────────────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $body  = getBody();
    $email = trim($body['email'] ?? '');
    $pass  = trim($body['password'] ?? '');

    if (!$email || !$pass) error('Email and password are required.');

    $db   = getDB();
    $stmt = $db->prepare('SELECT userID, name, email, passwordHash, role, isActive FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$user) error('Invalid email or password.');
    if (!$user['isActive']) error('Account is inactive. Contact administrator.');
    if (!password_verify($pass, $user['passwordHash'])) error('Invalid email or password.');

    $_SESSION['userID'] = $user['userID'];
    $_SESSION['name']   = $user['name'];
    $_SESSION['email']  = $user['email'];
    $_SESSION['role']   = $user['role'];

    success([
        'userID' => $user['userID'],
        'name'   => $user['name'],
        'email'  => $user['email'],
        'role'   => $user['role'],
    ], 'Login successful');
}

// ─── LOGOUT ───────────────────────────────────────────────
elseif ($method === 'POST' && $action === 'logout') {
    session_destroy();
    success([], 'Logged out');
}

// ─── GET CURRENT USER ─────────────────────────────────────
elseif ($method === 'GET' && $action === 'me') {
    $sess = requireAuth();
    success([
        'userID' => $sess['userID'],
        'name'   => $sess['name'],
        'email'  => $sess['email'],
        'role'   => $sess['role'],
    ]);
}

// ─── UPDATE PROFILE ───────────────────────────────────────
elseif ($method === 'PUT' && $action === 'update_profile') {
    $sess = requireAuth();
    $body = getBody();
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');
    if (!$name || !$email) error('Name and email required.');

    $db   = getDB();
    $stmt = $db->prepare('UPDATE users SET name=?, email=? WHERE userID=?');
    $stmt->bind_param('ssi', $name, $email, $sess['userID']);
    $stmt->execute();
    $stmt->close();
    $db->close();

    $_SESSION['name']  = $name;
    $_SESSION['email'] = $email;
    success([], 'Profile updated');
}

// ─── RESET PASSWORD ───────────────────────────────────────
elseif ($method === 'PUT' && $action === 'reset_password') {
    $sess    = requireAuth();
    $body    = getBody();
    $current = $body['current_password'] ?? '';
    $newPass = $body['new_password']     ?? '';
    if (!$current || !$newPass) error('Both passwords required.');

    $db   = getDB();
    $stmt = $db->prepare('SELECT passwordHash FROM users WHERE userID=?');
    $stmt->bind_param('i', $sess['userID']);
    $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($current, $row['passwordHash'])) error('Current password is incorrect.');

    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE users SET passwordHash=? WHERE userID=?');
    $stmt->bind_param('si', $hash, $sess['userID']);
    $stmt->execute();
    $stmt->close();
    $db->close();
    success([], 'Password updated');
}

else { error('Unknown action', 404); }
