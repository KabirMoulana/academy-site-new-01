<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db = getDB();
    $rows = $db->query(
        'SELECT e.*, CONCAT(u.name) AS studentName, b.batchName
         FROM enrollments e
         JOIN students st ON e.studentID = st.studentID
         JOIN users u ON st.userID = u.userID
         JOIN batches b ON e.batchID = b.batchID
         ORDER BY e.enrollDate DESC'
    )->fetch_all(MYSQLI_ASSOC);
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'enroll') {
    requireAuth();
    $b = getBody();
    $studentID = (int)($b['studentID'] ?? 0);
    $batchID   = (int)($b['batchID']   ?? 0);
    $date      = $b['enrollDate'] ?? date('Y-m-d');
    if (!$studentID || !$batchID) error('Student and batch required.');
    $db = getDB();
    $s = $db->prepare('INSERT INTO enrollments (studentID,batchID,enrollDate,status) VALUES (?,?,?,\'active\')');
    $s->bind_param('iis',$studentID,$batchID,$date); $s->execute();
    $newID = $db->insert_id; $s->close(); $db->close();
    success(['enrollmentID'=>$newID],'Student enrolled');
}
elseif ($method === 'PUT' && $action === 'unenroll' && $id) {
    requireAuth();
    $db = getDB();
    $today = date('Y-m-d');
    $s = $db->prepare('UPDATE enrollments SET status=\'inactive\', unenrollDate=? WHERE enrollmentID=?');
    $s->bind_param('si',$today,$id); $s->execute(); $s->close(); $db->close();
    success([],'Student unenrolled');
}
elseif ($method === 'PUT' && $action === 'reenroll' && $id) {
    requireAuth();
    $db = getDB();
    $s = $db->prepare('UPDATE enrollments SET status=\'active\', unenrollDate=NULL WHERE enrollmentID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Student re-enrolled');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b = getBody();
    $status = $b['status'] ?? 'active';
    $db = getDB();
    $s = $db->prepare('UPDATE enrollments SET status=? WHERE enrollmentID=?');
    $s->bind_param('si',$status,$id); $s->execute(); $s->close(); $db->close();
    success([],'Enrollment updated');
}
else { error('Unknown action',404); }
