<?php
// ─── SUBJECTS API ─────────────────────────────────────────
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db = getDB();
    $rows = $db->query('SELECT * FROM subjects ORDER BY name')->fetch_all(MYSQLI_ASSOC);
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'create') {
    requireAuth();
    $b = getBody();
    $name=$b['name']??''; $syl=$b['syllabus']??''; $lvl=$b['level']??''; $desc=$b['subjectDescription']??'';
    if (!$name) error('Subject name required.');
    $db = getDB();
    $s = $db->prepare('INSERT INTO subjects (name,syllabus,level,subjectDescription) VALUES (?,?,?,?)');
    $s->bind_param('ssss',$name,$syl,$lvl,$desc); $s->execute();
    $newID = $db->insert_id; $s->close(); $db->close();
    success(['subjectID'=>$newID],'Subject created');
}
elseif ($method === 'PUT' && $action === 'update' && $id) {
    requireAuth();
    $b = getBody();
    $name=$b['name']??''; $syl=$b['syllabus']??''; $lvl=$b['level']??''; $desc=$b['subjectDescription']??'';
    $db = getDB();
    $s = $db->prepare('UPDATE subjects SET name=?,syllabus=?,level=?,subjectDescription=? WHERE subjectID=?');
    $s->bind_param('ssssi',$name,$syl,$lvl,$desc,$id); $s->execute(); $s->close(); $db->close();
    success([],'Subject updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db = getDB();
    $s = $db->prepare('DELETE FROM subjects WHERE subjectID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Subject deleted');
}
else { error('Unknown action',404); }
