<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db = getDB();
    $studentID  = $_GET['studentID'] ?? '';
    $subjectID  = $_GET['subjectID'] ?? '';
    if ($studentID) {
        $s=$db->prepare('SELECT f.*,su.name AS subjectName FROM feedback f JOIN subjects su ON f.subjectID=su.subjectID WHERE f.studentID=? ORDER BY f.givenAt DESC');
        $s->bind_param('i',(int)$studentID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT f.*,u.name AS studentName,su.name AS subjectName FROM feedback f JOIN students st ON f.studentID=st.studentID JOIN users u ON st.userID=u.userID JOIN subjects su ON f.subjectID=su.subjectID ORDER BY f.givenAt DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'provide') {
    requireAuth();
    $b=getBody();
    $stID=(int)($b['studentID']??0); $subID=(int)($b['subjectID']??0);
    $comment=$b['comment']??''; $rating=(int)($b['feedbackRating']??5);
    if (!$stID||!$subID) error('Student and subject required.');
    $db=getDB();
    $s=$db->prepare('INSERT INTO feedback (studentID,subjectID,comment,feedbackRating) VALUES (?,?,?,?)');
    $s->bind_param('iisi',$stID,$subID,$comment,$rating);
    $s->execute(); $newID=$db->insert_id; $s->close(); $db->close();
    success(['feedbackID'=>$newID],'Feedback submitted');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=getBody(); $comment=$b['comment']??''; $rating=(int)($b['feedbackRating']??5); $status=$b['feedbackStatus']??'Pending';
    $db=getDB();
    $s=$db->prepare('UPDATE feedback SET comment=?,feedbackRating=?,feedbackStatus=? WHERE feedbackID=?');
    $s->bind_param('sisi',$comment,$rating,$status,$id); $s->execute(); $s->close(); $db->close();
    success([],'Feedback updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM feedback WHERE feedbackID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Feedback deleted');
}
else { error('Unknown action',404); }
