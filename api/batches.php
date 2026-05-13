<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db = getDB();
    $rows = $db->query('SELECT * FROM batches ORDER BY batchStartDate DESC')->fetch_all(MYSQLI_ASSOC);
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'create') {
    requireAuth();
    $b = getBody();
    $name=$b['batchName']??''; $sched=$b['schedule']??''; $mode=$b['mode']??'online';
    $start=$b['batchStartDate']??null; $end=$b['batchEndDate']??null;
    if (!$name) error('Batch name required.');
    $db = getDB();
    $s = $db->prepare('INSERT INTO batches (batchName,schedule,mode,batchStartDate,batchEndDate) VALUES (?,?,?,?,?)');
    $s->bind_param('sssss',$name,$sched,$mode,$start,$end); $s->execute();
    $newID = $db->insert_id; $s->close(); $db->close();
    success(['batchID'=>$newID],'Batch created');
}
elseif ($method === 'PUT' && $action === 'update' && $id) {
    requireAuth();
    $b = getBody();
    $name=$b['batchName']??''; $sched=$b['schedule']??''; $mode=$b['mode']??'online';
    $start=$b['batchStartDate']??null; $end=$b['batchEndDate']??null;
    $db = getDB();
    $s = $db->prepare('UPDATE batches SET batchName=?,schedule=?,mode=?,batchStartDate=?,batchEndDate=? WHERE batchID=?');
    $s->bind_param('sssssi',$name,$sched,$mode,$start,$end,$id); $s->execute(); $s->close(); $db->close();
    success([],'Batch updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db = getDB();
    $s = $db->prepare('DELETE FROM batches WHERE batchID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Batch deleted');
}
else { error('Unknown action',404); }
