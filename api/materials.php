<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db=getDB();
    $subjectID=$_GET['subjectID']??'';
    if ($subjectID) {
        $s=$db->prepare('SELECT sm.*,su.name AS subjectName,u.name AS lecturerName FROM study_materials sm JOIN subjects su ON sm.subjectID=su.subjectID JOIN lecturers l ON sm.lecturerID=l.lecturerID JOIN users u ON l.userID=u.userID WHERE sm.subjectID=? ORDER BY sm.uploadedAt DESC');
        $s->bind_param('i',(int)$subjectID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT sm.*,su.name AS subjectName,u.name AS lecturerName FROM study_materials sm JOIN subjects su ON sm.subjectID=su.subjectID JOIN lecturers l ON sm.lecturerID=l.lecturerID JOIN users u ON l.userID=u.userID ORDER BY sm.uploadedAt DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'upload') {
    requireAuth();
    $b=getBody();
    $subID=(int)($b['subjectID']??0); $lecID=(int)($b['lecturerID']??0);
    $title=$b['title']??''; $url=$b['fileURL']??''; $type=$b['fileType']??'';
    if (!$subID||!$lecID||!$title) error('Subject, lecturer and title required.');
    $db=getDB();
    $s=$db->prepare('INSERT INTO study_materials (subjectID,lecturerID,title,fileURL,fileType) VALUES (?,?,?,?,?)');
    $s->bind_param('iisss',$subID,$lecID,$title,$url,$type);
    $s->execute(); $newID=$db->insert_id; $s->close(); $db->close();
    success(['materialID'=>$newID],'Material uploaded');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=getBody(); $title=$b['title']??''; $url=$b['fileURL']??''; $type=$b['fileType']??'';
    $db=getDB();
    $s=$db->prepare('UPDATE study_materials SET title=?,fileURL=?,fileType=? WHERE materialID=?');
    $s->bind_param('sssi',$title,$url,$type,$id); $s->execute(); $s->close(); $db->close();
    success([],'Material updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM study_materials WHERE materialID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Material deleted');
}
else { error('Unknown action',404); }
