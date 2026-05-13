<?php
require_once '../config/db.php';
setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db   = getDB();
    $rows = $db->query('SELECT * FROM reports ORDER BY generatedAt DESC')->fetch_all(MYSQLI_ASSOC);
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'generate') {
    requireAuth();
    $b = getBody();
    $type   = $b['type']         ?? 'Academic';
    $period = $b['reportPeriod'] ?? date('Y-m');
    $fileURL= $b['reportFileURL']?? '';
    $db = getDB();
    $s = $db->prepare('INSERT INTO reports (type,reportPeriod,reportFileURL) VALUES (?,?,?)');
    $s->bind_param('sss',$type,$period,$fileURL); $s->execute();
    $newID=$db->insert_id; $s->close(); $db->close();
    success(['reportID'=>$newID],'Report generated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM reports WHERE reportID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Report deleted');
}
else { error('Unknown action',404); }
