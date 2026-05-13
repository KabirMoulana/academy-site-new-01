<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ─── PERFORMANCE POINTS ───────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db=getDB();
    $studentID=$_GET['studentID']??'';
    if ($studentID) {
        $s=$db->prepare('SELECT * FROM performance_points WHERE studentID=? ORDER BY awardedAt DESC');
        $s->bind_param('i',(int)$studentID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT pp.*,u.name AS studentName FROM performance_points pp JOIN students st ON pp.studentID=st.studentID JOIN users u ON st.userID=u.userID ORDER BY pp.awardedAt DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'GET' && $action === 'total') {
    requireAuth();
    $studentID=(int)($_GET['studentID']??0);
    if (!$studentID) error('studentID required.');
    $db=getDB();
    $s=$db->prepare('SELECT COALESCE(SUM(points),0) AS total FROM performance_points WHERE studentID=?');
    $s->bind_param('i',$studentID); $s->execute();
    $row=$s->get_result()->fetch_assoc(); $s->close(); $db->close();
    success(['total'=>(int)$row['total']]);
}
elseif ($method === 'POST' && $action === 'add') {
    requireAuth();
    $b=getBody();
    $stID=(int)($b['studentID']??0); $pts=(int)($b['points']??0); $reason=$b['pointReason']??'';
    if (!$stID) error('studentID required.');
    $db=getDB();
    $s=$db->prepare('INSERT INTO performance_points (studentID,points,pointReason) VALUES (?,?,?)');
    $s->bind_param('iis',$stID,$pts,$reason);
    $s->execute(); $newID=$db->insert_id; $s->close();
    // Update leaderboard
    updateLeaderboard($db, $stID);
    $db->close();
    success(['pointID'=>$newID],'Points added');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=getBody(); $pts=(int)($b['points']??0); $reason=$b['pointReason']??'';
    $db=getDB();
    $s=$db->prepare('UPDATE performance_points SET points=?,pointReason=? WHERE pointID=?');
    $s->bind_param('isi',$pts,$reason,$id); $s->execute(); $s->close();
    // Recalculate leaderboard for student
    $s2=$db->prepare('SELECT studentID FROM performance_points WHERE pointID=?');
    $s2->bind_param('i',$id); $s2->execute();
    $row=$s2->get_result()->fetch_assoc(); $s2->close();
    if ($row) updateLeaderboard($db,(int)$row['studentID']);
    $db->close();
    success([],'Points updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('SELECT studentID FROM performance_points WHERE pointID=?');
    $s->bind_param('i',$id); $s->execute();
    $row=$s->get_result()->fetch_assoc(); $s->close();
    $s2=$db->prepare('DELETE FROM performance_points WHERE pointID=?');
    $s2->bind_param('i',$id); $s2->execute(); $s2->close();
    if ($row) updateLeaderboard($db,(int)$row['studentID']);
    $db->close();
    success([],'Points deleted');
}

// ─── LEADERBOARD ──────────────────────────────────────────
elseif ($method === 'GET' && $action === 'leaderboard') {
    requireAuth();
    $db=getDB();
    $rows=$db->query('SELECT lb.*,u.name AS studentName FROM leaderboard lb JOIN students st ON lb.studentID=st.studentID JOIN users u ON st.userID=u.userID ORDER BY lb.totalPoints DESC LIMIT 50')->fetch_all(MYSQLI_ASSOC);
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'rebuild_leaderboard') {
    requireAuth();
    $db=getDB();
    $students=$db->query('SELECT studentID FROM students')->fetch_all(MYSQLI_ASSOC);
    foreach ($students as $st) updateLeaderboard($db,(int)$st['studentID']);
    $db->close();
    success([],'Leaderboard rebuilt');
}
elseif ($method === 'DELETE' && $action === 'reset_leaderboard') {
    requireAuth();
    $db=getDB();
    $db->query('DELETE FROM leaderboard');
    $db->close();
    success([],'Leaderboard reset');
}
else { error('Unknown action',404); }

function updateLeaderboard($db, $studentID) {
    $s=$db->prepare('SELECT COALESCE(SUM(points),0) AS total FROM performance_points WHERE studentID=?');
    $s->bind_param('i',$studentID); $s->execute();
    $row=$s->get_result()->fetch_assoc(); $s->close();
    $total=(int)$row['total'];
    $s2=$db->prepare('INSERT INTO leaderboard (studentID,totalPoints) VALUES (?,?) ON DUPLICATE KEY UPDATE totalPoints=?');
    $s2->bind_param('iii',$studentID,$total,$total); $s2->execute(); $s2->close();
    // Recalculate ranks
    $db->query('SET @rank=0; UPDATE leaderboard SET `rank`=(@rank:=@rank+1) ORDER BY totalPoints DESC');
}
