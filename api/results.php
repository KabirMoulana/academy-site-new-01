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
    $lecID     = $_GET['lecturerID'] ?? '';
    if ($studentID) {
        $s=$db->prepare('SELECT er.*,su.name AS subjectName FROM exam_results er JOIN subjects su ON er.subjectID=su.subjectID WHERE er.studentID=? ORDER BY er.examDate DESC');
        $s->bind_param('i',(int)$studentID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } elseif ($lecID) {
        $s=$db->prepare('SELECT er.*,u.name AS studentName,su.name AS subjectName FROM exam_results er JOIN students st ON er.studentID=st.studentID JOIN users u ON st.userID=u.userID JOIN subjects su ON er.subjectID=su.subjectID WHERE er.lecturerID=? ORDER BY er.examDate DESC');
        $s->bind_param('i',(int)$lecID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT er.*,u.name AS studentName,su.name AS subjectName FROM exam_results er JOIN students st ON er.studentID=st.studentID JOIN users u ON st.userID=u.userID JOIN subjects su ON er.subjectID=su.subjectID ORDER BY er.examDate DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'upload') {
    requireAuth();
    $b=getBody();
    $stID=(int)($b['studentID']??0); $subID=(int)($b['subjectID']??0); $lecID=(int)($b['lecturerID']??0);
    $marks=(float)($b['marks']??0); $grade=$b['resultGrade']??''; $date=$b['examDate']??date('Y-m-d');
    if (!$stID||!$subID||!$lecID) error('Student, subject and lecturer required.');
    $db=getDB();
    $s=$db->prepare('INSERT INTO exam_results (studentID,subjectID,lecturerID,marks,resultGrade,examDate) VALUES (?,?,?,?,?,?)');
    $s->bind_param('iiidss',$stID,$subID,$lecID,$marks,$grade,$date);
    $s->execute(); $newID=$db->insert_id; $s->close(); $db->close();
    success(['resultID'=>$newID],'Result uploaded');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=getBody();
    $marks=(float)($b['marks']??0); $grade=$b['resultGrade']??''; $date=$b['examDate']??date('Y-m-d');
    $db=getDB();
    $s=$db->prepare('UPDATE exam_results SET marks=?,resultGrade=?,examDate=? WHERE resultID=?');
    $s->bind_param('dssi',$marks,$grade,$date,$id); $s->execute(); $s->close(); $db->close();
    success([],'Result updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM exam_results WHERE resultID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Result deleted');
}
else { error('Unknown action',404); }
