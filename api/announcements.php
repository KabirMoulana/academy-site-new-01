<?php
require_once '../config/db.php';
setHeaders();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db   = getDB();
    $status = $_GET['status'] ?? '';
    if ($status) {
        $s=$db->prepare('SELECT a.*,u.name AS postedBy FROM announcements a JOIN users u ON a.userID=u.userID WHERE a.announcementStatus=? ORDER BY a.postedAt DESC');
        $s->bind_param('s',$status); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT a.*,u.name AS postedBy FROM announcements a JOIN users u ON a.userID=u.userID ORDER BY a.postedAt DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'post') {
    requireAuth();
    $b=getBody(); $sess=requireAuth();
    $title=$b['title']??''; $content=$b['content']??''; $status=$b['announcementStatus']??'Published';
    $userID=$sess['userID'];
    if (!$title) error('Title required.');
    $db=getDB();
    $s=$db->prepare('INSERT INTO announcements (userID,title,content,announcementStatus) VALUES (?,?,?,?)');
    $s->bind_param('isss',$userID,$title,$content,$status);
    $s->execute(); $newID=$db->insert_id; $s->close(); $db->close();
    success(['announcementID'=>$newID],'Announcement posted');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=getBody(); $title=$b['title']??''; $content=$b['content']??''; $status=$b['announcementStatus']??'Published';
    $db=getDB();
    $s=$db->prepare('UPDATE announcements SET title=?,content=?,announcementStatus=? WHERE announcementID=?');
    $s->bind_param('sssi',$title,$content,$status,$id); $s->execute(); $s->close(); $db->close();
    success([],'Announcement updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM announcements WHERE announcementID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Announcement deleted');
}
else { error('Unknown action',404); }
