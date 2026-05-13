<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db = getDB();
    $studentID = $_GET['studentID'] ?? '';
    $sessionID = $_GET['sessionID'] ?? '';
    if ($studentID) {
        $s = $db->prepare('SELECT a.*,su.name AS subjectName,cs.date AS sessionDate FROM attendance a JOIN class_sessions cs ON a.sessionID=cs.sessionID JOIN subjects su ON cs.subjectID=su.subjectID WHERE a.studentID=? ORDER BY a.date DESC');
        $s->bind_param('i',(int)$studentID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } elseif ($sessionID) {
        $s = $db->prepare('SELECT a.*,u.name AS studentName FROM attendance a JOIN students st ON a.studentID=st.studentID JOIN users u ON st.userID=u.userID WHERE a.sessionID=? ORDER BY u.name');
        $s->bind_param('i',(int)$sessionID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT a.*,u.name AS studentName,su.name AS subjectName FROM attendance a JOIN students st ON a.studentID=st.studentID JOIN users u ON st.userID=u.userID JOIN class_sessions cs ON a.sessionID=cs.sessionID JOIN subjects su ON cs.subjectID=su.subjectID ORDER BY a.date DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'mark') {
    requireAuth();
    $b = getBody();
    $records = $b['records'] ?? []; // Array of {studentID, sessionID, status, note}
    if (empty($records)) error('No records provided.');
    $db = getDB();
    $today = date('Y-m-d');
    foreach ($records as $r) {
        $stID=(int)($r['studentID']??0); $sesID=(int)($r['sessionID']??0);
        $status=$r['status']??'present'; $note=$r['attendanceNote']??'';
        if (!$stID||!$sesID) continue;
        // Upsert
        $s=$db->prepare('INSERT INTO attendance (sessionID,studentID,date,status,attendanceNote) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE status=?,attendanceNote=?');
        $s->bind_param('iissss',$sesID,$stID,$today,$status,$note,$status,$note);
        $s->execute(); $s->close();
    }
    $db->close(); success([],'Attendance marked');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=getBody(); $status=$b['status']??'present'; $note=$b['attendanceNote']??'';
    $db=getDB();
    $s=$db->prepare('UPDATE attendance SET status=?,attendanceNote=? WHERE attendanceID=?');
    $s->bind_param('ssi',$status,$note,$id); $s->execute(); $s->close(); $db->close();
    success([],'Attendance updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM attendance WHERE attendanceID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Attendance deleted');
}
else { error('Unknown action',404); }
