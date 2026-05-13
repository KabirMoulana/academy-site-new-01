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
        'SELECT st.*, u.name, u.email, u.role, u.isActive
         FROM students st
         JOIN users u ON st.userID = u.userID
         WHERE st.userID = ?'
    );
    $s->bind_param('i', $userID);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close(); $db->close();
    if (!$row) error('Student not found', 404);
    success($row);
}

// ─── LIST ALL STUDENTS ────────────────────────────────────
elseif ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db   = getDB();
    $rows = $db->query(
        'SELECT st.*, u.name, u.email, u.isActive, u.createdAt
         FROM students st
         JOIN users u ON st.userID = u.userID
         ORDER BY u.name'
    )->fetch_all(MYSQLI_ASSOC);
    $db->close();
    success($rows);
}

// ─── GET SINGLE STUDENT ───────────────────────────────────
elseif ($method === 'GET' && $action === 'get' && $id) {
    requireAuth();
    $db = getDB();
    $s  = $db->prepare(
        'SELECT st.*, u.name, u.email, u.isActive
         FROM students st
         JOIN users u ON st.userID = u.userID
         WHERE st.studentID = ?'
    );
    $s->bind_param('i', $id);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close(); $db->close();
    if (!$row) error('Student not found', 404);
    success($row);
}

// ─── ADD STUDENT (also creates user) ─────────────────────
elseif ($method === 'POST' && $action === 'add') {
    requireAuth();
    $b    = getBody();
    $name = trim($b['name']  ?? '');
    $email= trim($b['email'] ?? '');
    $pass = trim($b['password'] ?? 'password');
    $type = $b['type']        ?? 'online';
    $grade= $b['grade']       ?? '';
    $dob  = $b['dateOfBirth'] ?? null;

    if (!$name || !$email) error('Name and email required.');

    $db   = getDB();
    // Check duplicate email
    $chk  = $db->prepare('SELECT userID FROM users WHERE email=?');
    $chk->bind_param('s', $email); $chk->execute();
    if ($chk->get_result()->num_rows > 0) { $db->close(); error('Email already exists.'); }
    $chk->close();

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $s    = $db->prepare('INSERT INTO users (name,email,passwordHash,role) VALUES (?,?,?,\'student\')');
    $s->bind_param('sss', $name, $email, $hash);
    $s->execute();
    $userID = $db->insert_id;
    $s->close();

    $today = date('Y-m-d');
    $s2 = $db->prepare('INSERT INTO students (userID,type,grade,dateOfBirth,studentGrantedDate) VALUES (?,?,?,?,?)');
    $s2->bind_param('issss', $userID, $type, $grade, $dob, $today);
    $s2->execute();
    $studentID = $db->insert_id;
    $s2->close(); $db->close();

    success(['studentID' => $studentID, 'userID' => $userID], 'Student added');
}

// ─── EDIT STUDENT ─────────────────────────────────────────
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b    = getBody();
    $name = trim($b['name']  ?? '');
    $email= trim($b['email'] ?? '');
    $type = $b['type']        ?? 'online';
    $grade= $b['grade']       ?? '';
    $dob  = $b['dateOfBirth'] ?? null;

    $db = getDB();
    // Get userID for this student
    $s  = $db->prepare('SELECT userID FROM students WHERE studentID=?');
    $s->bind_param('i', $id); $s->execute();
    $row = $s->get_result()->fetch_assoc(); $s->close();
    if (!$row) { $db->close(); error('Student not found', 404); }

    $s2 = $db->prepare('UPDATE users SET name=?,email=? WHERE userID=?');
    $s2->bind_param('ssi', $name, $email, $row['userID']); $s2->execute(); $s2->close();

    $s3 = $db->prepare('UPDATE students SET type=?,grade=?,dateOfBirth=? WHERE studentID=?');
    $s3->bind_param('sssi', $type, $grade, $dob, $id); $s3->execute(); $s3->close();
    $db->close();
    success([], 'Student updated');
}

// ─── DELETE STUDENT ───────────────────────────────────────
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db = getDB();
    // Deleting the user cascades to student
    $s = $db->prepare('SELECT userID FROM students WHERE studentID=?');
    $s->bind_param('i', $id); $s->execute();
    $row = $s->get_result()->fetch_assoc(); $s->close();
    if ($row) {
        $del = $db->prepare('DELETE FROM users WHERE userID=?');
        $del->bind_param('i', $row['userID']); $del->execute(); $del->close();
    }
    $db->close();
    success([], 'Student deleted');
}

// ─── VIEW STATISTICS ──────────────────────────────────────
elseif ($method === 'GET' && $action === 'statistics' && $id) {
    requireAuth();
    $db = getDB();

    // Attendance %
    $s = $db->prepare('SELECT COUNT(*) AS total, SUM(status=\'present\') AS present FROM attendance WHERE studentID=?');
    $s->bind_param('i', $id); $s->execute();
    $attRow = $s->get_result()->fetch_assoc(); $s->close();
    $attPct = $attRow['total'] > 0 ? round($attRow['present'] / $attRow['total'] * 100) : 0;

    // Average marks
    $s2 = $db->prepare('SELECT AVG(marks) AS avg FROM exam_results WHERE studentID=?');
    $s2->bind_param('i', $id); $s2->execute();
    $resRow = $s2->get_result()->fetch_assoc(); $s2->close();

    // Total performance points
    $s3 = $db->prepare('SELECT COALESCE(SUM(points),0) AS total FROM performance_points WHERE studentID=?');
    $s3->bind_param('i', $id); $s3->execute();
    $ptsRow = $s3->get_result()->fetch_assoc(); $s3->close();

    $db->close();
    success([
        'attendanceRate'  => $attPct,
        'averageMarks'    => round((float)($resRow['avg'] ?? 0), 1),
        'totalPoints'     => (int)$ptsRow['total'],
    ]);
}

else { error('Unknown action', 404); }
