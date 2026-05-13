<?php
require_once '../config/db.php';
setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'getByUser') {
    requireAuth();
    $userID = (int)($_GET['userID'] ?? 0);
    if (!$userID) error('userID required.');
    $db = getDB();
    $s  = $db->prepare('SELECT p.*, u.name, u.email FROM parents p JOIN users u ON p.userID=u.userID WHERE p.userID=?');
    $s->bind_param('i', $userID); $s->execute();
    $row = $s->get_result()->fetch_assoc(); $s->close(); $db->close();
    if (!$row) error('Parent not found', 404);
    success($row);
}
elseif ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db   = getDB();
    $rows = $db->query('SELECT p.*, u.name, u.email, u.isActive FROM parents p JOIN users u ON p.userID=u.userID ORDER BY u.name')->fetch_all(MYSQLI_ASSOC);
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'add') {
    requireAuth();
    $b = getBody();
    $name=$b['name']??''; $email=$b['email']??''; $pass=$b['password']??'password';
    $contact=$b['contactNo']??''; $linkedID=$b['linkedStudentID']??null;
    if (!$name||!$email) error('Name and email required.');
    $db = getDB();
    $chk=$db->prepare('SELECT userID FROM users WHERE email=?');
    $chk->bind_param('s',$email); $chk->execute();
    if ($chk->get_result()->num_rows>0) { $db->close(); error('Email exists.'); }
    $chk->close();
    $hash=password_hash($pass,PASSWORD_BCRYPT);
    $s=$db->prepare('INSERT INTO users (name,email,passwordHash,role) VALUES (?,?,?,\'parent\')');
    $s->bind_param('sss',$name,$email,$hash); $s->execute();
    $userID=$db->insert_id; $s->close();
    $today=date('Y-m-d');
    $s2=$db->prepare('INSERT INTO parents (userID,contactNo,linkedStudentID,parentGrantedDate) VALUES (?,?,?,?)');
    $s2->bind_param('isis',$userID,$contact,$linkedID,$today); $s2->execute();
    $newID=$db->insert_id; $s2->close(); $db->close();
    success(['parentID'=>$newID],'Parent added');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=$getBody(); $contact=$b['contactNo']??''; $linkedID=$b['linkedStudentID']??null;
    $db=getDB();
    $s=$db->prepare('UPDATE parents SET contactNo=?,linkedStudentID=? WHERE parentID=?');
    $s->bind_param('sii',$contact,$linkedID,$id); $s->execute(); $s->close(); $db->close();
    success([],'Parent updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('SELECT userID FROM parents WHERE parentID=?');
    $s->bind_param('i',$id); $s->execute();
    $row=$s->get_result()->fetch_assoc(); $s->close();
    if ($row) { $del=$db->prepare('DELETE FROM users WHERE userID=?'); $del->bind_param('i',$row['userID']); $del->execute(); $del->close(); }
    $db->close(); success([],'Parent deleted');
}
else { error('Unknown action',404); }
