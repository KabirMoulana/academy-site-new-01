<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db = getDB();
    $lecID = $_GET['lecturerID'] ?? '';
    if ($lecID) {
        $s = $db->prepare('SELECT cs.*,b.batchName,su.name AS subjectName FROM class_sessions cs JOIN batches b ON cs.batchID=b.batchID JOIN subjects su ON cs.subjectID=su.subjectID WHERE cs.lecturerID=? ORDER BY cs.date DESC');
        $s->bind_param('i',(int)$lecID); $s->execute();
        $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows = $db->query('SELECT cs.*,b.batchName,su.name AS subjectName FROM class_sessions cs JOIN batches b ON cs.batchID=b.batchID JOIN subjects su ON cs.subjectID=su.subjectID ORDER BY cs.date DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'create') {
    requireAuth();
    $b = getBody();
    $batchID=$b['batchID']??0; $subjectID=$b['subjectID']??0; $lecID=$b['lecturerID']??0;
    $date=$b['date']??''; $link=$b['classLink']??''; $status=$b['sessionStatus']??'Upcoming';
    if (!$batchID||!$subjectID||!$lecID||!$date) error('Batch, subject, lecturer and date required.');
    $db = getDB();
    $s = $db->prepare('INSERT INTO class_sessions (batchID,subjectID,lecturerID,date,classLink,sessionStatus) VALUES (?,?,?,?,?,?)');
    $s->bind_param('iiiiss',(int)$batchID,(int)$subjectID,(int)$lecID,$date,$link,$status);
    $s->execute(); $newID=$db->insert_id; $s->close(); $db->close();
    success(['sessionID'=>$newID],'Session created');
}
elseif ($method === 'PUT' && $action === 'upload_link' && $id) {
    requireAuth();
    $b = getBody(); $link=$b['classLink']??'';
    $db=getDB();
    $s=$db->prepare('UPDATE class_sessions SET classLink=? WHERE sessionID=?');
    $s->bind_param('si',$link,$id); $s->execute(); $s->close(); $db->close();
    success([],'Link uploaded');
}
elseif ($method === 'PUT' && $action === 'upload_recording' && $id) {
    requireAuth();
    $b = getBody(); $rec=$b['recording']??'';
    $db=getDB();
    $s=$db->prepare('UPDATE class_sessions SET recording=?,sessionStatus=\'Completed\' WHERE sessionID=?');
    $s->bind_param('si',$rec,$id); $s->execute(); $s->close(); $db->close();
    success([],'Recording uploaded');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM class_sessions WHERE sessionID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Session deleted');
}
else { error('Unknown action',404); }
