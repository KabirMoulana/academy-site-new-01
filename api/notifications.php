<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    $sess = requireAuth();
    $userID = $_GET['userID'] ?? $sess['userID'];
    $db=getDB();
    $s=$db->prepare('SELECT * FROM notifications WHERE userID=? ORDER BY sentAt DESC');
    $s->bind_param('i',(int)$userID); $s->execute();
    $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); $db->close();
    success($rows);
}
elseif ($method === 'POST' && $action === 'send') {
    requireAuth();
    $b=getBody();
    $userID=(int)($b['userID']??0); $msg=$b['message']??''; $type=$b['notificationType']??'General';
    if (!$userID||!$msg) error('UserID and message required.');
    $db=getDB();
    $s=$db->prepare('INSERT INTO notifications (userID,message,notificationType) VALUES (?,?,?)');
    $s->bind_param('iss',$userID,$msg,$type);
    $s->execute(); $s->close(); $db->close();
    success([],'Notification sent');
}
elseif ($method === 'PUT' && $action === 'mark_read' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('UPDATE notifications SET isRead=1 WHERE notificationID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Marked as read');
}
elseif ($method === 'PUT' && $action === 'mark_all_read') {
    $sess=requireAuth();
    $db=getDB();
    $s=$db->prepare('UPDATE notifications SET isRead=1 WHERE userID=?');
    $s->bind_param('i',$sess['userID']); $s->execute(); $s->close(); $db->close();
    success([],'All marked as read');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM notifications WHERE notificationID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Notification deleted');
}
else { error('Unknown action',404); }
