<?php
require_once '../config/db.php';
setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ─── LIST USERS ───────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $sess = requireAuth();
    $db   = getDB();
    $role = $_GET['role'] ?? '';
    if ($role) {
        $stmt = $db->prepare('SELECT userID,name,email,role,isActive,createdAt FROM users WHERE role=? ORDER BY createdAt DESC');
        $stmt->bind_param('s', $role);
    } else {
        $stmt = $db->prepare('SELECT userID,name,email,role,isActive,createdAt FROM users ORDER BY createdAt DESC');
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    success($rows);
}

// ─── GET SINGLE USER ──────────────────────────────────────
elseif ($method === 'GET' && $action === 'get' && $id) {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('SELECT userID,name,email,role,isActive,createdAt FROM users WHERE userID=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close(); $db->close();
    if (!$row) error('User not found', 404);
    success($row);
}

// ─── CREATE USER ──────────────────────────────────────────
elseif ($method === 'POST' && $action === 'create') {
    requireAuth();
    $body  = getBody();
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');
    $pass  = trim($body['password'] ?? '');
    $role  = trim($body['role']  ?? '');
    $validRoles = ['admin','manager','director','lecturer','student','parent','receptionist'];
    if (!$name || !$email || !$pass || !in_array($role, $validRoles)) error('All fields required and role must be valid.');

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db   = getDB();

    // Check duplicate email
    $chk = $db->prepare('SELECT userID FROM users WHERE email=?');
    $chk->bind_param('s', $email); $chk->execute();
    if ($chk->get_result()->num_rows > 0) { $db->close(); error('Email already exists.'); }
    $chk->close();

    $stmt = $db->prepare('INSERT INTO users (name,email,passwordHash,role) VALUES (?,?,?,?)');
    $stmt->bind_param('ssss', $name, $email, $hash, $role);
    $stmt->execute();
    $newID = $db->insert_id;
    $stmt->close();

    // Insert into role table
    insertRoleRecord($db, $role, $newID, $body);
    $db->close();
    success(['userID' => $newID], 'User created');
}

// ─── UPDATE USER ──────────────────────────────────────────
elseif ($method === 'PUT' && $action === 'update' && $id) {
    requireAuth();
    $body  = getBody();
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');
    $isActive = isset($body['isActive']) ? (int)$body['isActive'] : 1;
    if (!$name || !$email) error('Name and email required.');

    $db   = getDB();
    $stmt = $db->prepare('UPDATE users SET name=?, email=?, isActive=? WHERE userID=?');
    $stmt->bind_param('ssii', $name, $email, $isActive, $id);
    $stmt->execute();
    $stmt->close(); $db->close();
    success([], 'User updated');
}

// ─── DELETE USER ──────────────────────────────────────────
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM users WHERE userID=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close(); $db->close();
    success([], 'User deleted');
}

// ─── TOGGLE ACTIVE STATUS ─────────────────────────────────
elseif ($method === 'PUT' && $action === 'toggle_active' && $id) {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('UPDATE users SET isActive = NOT isActive WHERE userID=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close(); $db->close();
    success([], 'Status toggled');
}

else { error('Unknown action', 404); }

// ─── HELPER: Insert into role-specific table ──────────────
function insertRoleRecord($db, $role, $userID, $body) {
    $today = date('Y-m-d');
    switch ($role) {
        case 'admin':
            $lvl = $body['adminAccessLevel'] ?? 'Admin';
            $s = $db->prepare('INSERT INTO admins (userID,adminAccessLevel,adminGrantedDate) VALUES (?,?,?)');
            $s->bind_param('iss', $userID, $lvl, $today); $s->execute(); $s->close(); break;
        case 'manager':
            $dept = $body['department'] ?? '';
            $s = $db->prepare('INSERT INTO managers (userID,department,managerGrantedDate) VALUES (?,?,?)');
            $s->bind_param('iss', $userID, $dept, $today); $s->execute(); $s->close(); break;
        case 'director':
            $title = $body['title'] ?? '';
            $s = $db->prepare('INSERT INTO directors (userID,title,directorGrantedDate) VALUES (?,?,?)');
            $s->bind_param('iss', $userID, $title, $today); $s->execute(); $s->close(); break;
        case 'lecturer':
            $qual = $body['qualification'] ?? ''; $spec = $body['specialization'] ?? '';
            $s = $db->prepare('INSERT INTO lecturers (userID,qualification,specialization,lecturerGrantedDate) VALUES (?,?,?,?)');
            $s->bind_param('isss', $userID, $qual, $spec, $today); $s->execute(); $s->close(); break;
        case 'student':
            $type = $body['type'] ?? 'online'; $grade = $body['grade'] ?? ''; $dob = $body['dateOfBirth'] ?? null;
            $s = $db->prepare('INSERT INTO students (userID,type,grade,dateOfBirth,studentGrantedDate) VALUES (?,?,?,?,?)');
            $s->bind_param('issss', $userID, $type, $grade, $dob, $today); $s->execute(); $s->close(); break;
        case 'parent':
            $contact = $body['contactNo'] ?? ''; $linked = $body['linkedStudentID'] ?? null;
            $s = $db->prepare('INSERT INTO parents (userID,contactNo,linkedStudentID,parentGrantedDate) VALUES (?,?,?,?)');
            $s->bind_param('isis', $userID, $contact, $linked, $today); $s->execute(); $s->close(); break;
        case 'receptionist':
            $counter = $body['assignedCounter'] ?? '';
            $s = $db->prepare('INSERT INTO receptionists (userID,assignedCounter,receptionistGrantedDate) VALUES (?,?,?)');
            $s->bind_param('iss', $userID, $counter, $today); $s->execute(); $s->close(); break;
    }
}
