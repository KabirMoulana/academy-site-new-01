<?php
require_once '../config/db.php';
setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ─── GET BY USER ID ───────────────────────────────────────
if ($method === 'GET' && $action === 'getByUser') {
    requireAuth();
    $userID = (int)($_GET['userID'] ?? 0);
    if (!$userID) error('userID required.');
    $db = getDB();
    $s  = $db->prepare(
        'SELECT l.*, u.name, u.email FROM lecturers l
         JOIN users u ON l.userID = u.userID
         WHERE l.userID = ?'
    );
    $s->bind_param('i', $userID);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close(); $db->close();
    if (!$row) error('Lecturer not found', 404);
    success($row);
}

// ─── LIST ALL LECTURERS ───────────────────────────────────
elseif ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db   = getDB();
    $rows = $db->query(
        'SELECT l.*, u.name, u.email, u.isActive
         FROM lecturers l
         JOIN users u ON l.userID = u.userID
         ORDER BY u.name'
    )->fetch_all(MYSQLI_ASSOC);
    $db->close();
    success($rows);
}

// ─── GET SINGLE LECTURER ──────────────────────────────────
elseif ($method === 'GET' && $action === 'get' && $id) {
    requireAuth();
    $db = getDB();
    $s  = $db->prepare(
        'SELECT l.*, u.name, u.email, u.isActive
         FROM lecturers l
         JOIN users u ON l.userID = u.userID
         WHERE l.lecturerID = ?'
    );
    $s->bind_param('i', $id);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close(); $db->close();
    if (!$row) error('Lecturer not found', 404);
    success($row);
}

// ─── ADD LECTURER ─────────────────────────────────────────
elseif ($method === 'POST' && $action === 'add') {
    requireAuth();
    $b    = getBody();
    $name = trim($b['name']  ?? '');
    $email= trim($b['email'] ?? '');
    $pass = trim($b['password'] ?? 'password');
    $qual = $b['qualification']  ?? '';
    $spec = $b['specialization'] ?? '';

    if (!$name || !$email) error('Name and email required.');

    $db   = getDB();
    $chk  = $db->prepare('SELECT userID FROM users WHERE email=?');
    $chk->bind_param('s', $email); $chk->execute();
    if ($chk->get_result()->num_rows > 0) { $db->close(); error('Email already exists.'); }
    $chk->close();

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $s    = $db->prepare('INSERT INTO users (name,email,passwordHash,role) VALUES (?,?,?,\'lecturer\')');
    $s->bind_param('sss', $name, $email, $hash);
    $s->execute();
    $userID = $db->insert_id;
    $s->close();

    $today = date('Y-m-d');
    $s2 = $db->prepare('INSERT INTO lecturers (userID,qualification,specialization,lecturerGrantedDate) VALUES (?,?,?,?)');
    $s2->bind_param('isss', $userID, $qual, $spec, $today);
    $s2->execute();
    $lecturerID = $db->insert_id;
    $s2->close(); $db->close();

    success(['lecturerID' => $lecturerID, 'userID' => $userID], 'Lecturer added');
}

// ─── EDIT LECTURER ────────────────────────────────────────
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b    = getBody();
    $name = trim($b['name']  ?? '');
    $email= trim($b['email'] ?? '');
    $qual = $b['qualification']  ?? '';
    $spec = $b['specialization'] ?? '';

    $db = getDB();
    $s  = $db->prepare('SELECT userID FROM lecturers WHERE lecturerID=?');
    $s->bind_param('i', $id); $s->execute();
    $row = $s->get_result()->fetch_assoc(); $s->close();
    if (!$row) { $db->close(); error('Lecturer not found', 404); }

    $s2 = $db->prepare('UPDATE users SET name=?,email=? WHERE userID=?');
    $s2->bind_param('ssi', $name, $email, $row['userID']); $s2->execute(); $s2->close();

    $s3 = $db->prepare('UPDATE lecturers SET qualification=?,specialization=? WHERE lecturerID=?');
    $s3->bind_param('ssi', $qual, $spec, $id); $s3->execute(); $s3->close();
    $db->close();
    success([], 'Lecturer updated');
}

// ─── REVOKE / GRANT ACCESS ────────────────────────────────
elseif ($method === 'PUT' && $action === 'revoke' && $id) {
    requireAuth();
    $db = getDB();
    $today = date('Y-m-d');
    $s = $db->prepare('UPDATE lecturers SET lecturerRevokedDate=? WHERE lecturerID=?');
    $s->bind_param('si', $today, $id); $s->execute(); $s->close();
    // Deactivate user
    $s2 = $db->prepare('UPDATE users u JOIN lecturers l ON u.userID=l.userID SET u.isActive=0 WHERE l.lecturerID=?');
    $s2->bind_param('i', $id); $s2->execute(); $s2->close();
    $db->close();
    success([], 'Access revoked');
}

elseif ($method === 'PUT' && $action === 'grant' && $id) {
    requireAuth();
    $db = getDB();
    $today = date('Y-m-d');
    $s = $db->prepare('UPDATE lecturers SET lecturerGrantedDate=?,lecturerRevokedDate=NULL WHERE lecturerID=?');
    $s->bind_param('si', $today, $id); $s->execute(); $s->close();
    $s2 = $db->prepare('UPDATE users u JOIN lecturers l ON u.userID=l.userID SET u.isActive=1 WHERE l.lecturerID=?');
    $s2->bind_param('i', $id); $s2->execute(); $s2->close();
    $db->close();
    success([], 'Access granted');
}

// ─── DELETE LECTURER ──────────────────────────────────────
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db = getDB();
    $s  = $db->prepare('SELECT userID FROM lecturers WHERE lecturerID=?');
    $s->bind_param('i', $id); $s->execute();
    $row = $s->get_result()->fetch_assoc(); $s->close();
    if ($row) {
        $del = $db->prepare('DELETE FROM users WHERE userID=?');
        $del->bind_param('i', $row['userID']); $del->execute(); $del->close();
    }
    $db->close();
    success([], 'Lecturer deleted');
}

else { error('Unknown action', 404); }
