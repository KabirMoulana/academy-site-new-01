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
    if ($studentID) {
        $s=$db->prepare('SELECT p.*,u.name AS studentName FROM payments p JOIN students st ON p.studentID=st.studentID JOIN users u ON st.userID=u.userID WHERE p.studentID=? ORDER BY p.month DESC');
        $s->bind_param('i',(int)$studentID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT p.*,u.name AS studentName FROM payments p JOIN students st ON p.studentID=st.studentID JOIN users u ON st.userID=u.userID ORDER BY p.month DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'record') {
    requireAuth();
    $b=getBody();
    $stID=(int)($b['studentID']??0); $amount=(float)($b['amount']??0);
    $month=$b['month']??''; $status=$b['status']??'pending';
    if (!$stID||!$amount||!$month) error('Student, amount and month required.');
    $db=getDB();
    $paidAt = $status==='approved' ? date('Y-m-d H:i:s') : null;
    $s=$db->prepare('INSERT INTO payments (studentID,amount,month,status,paidAt) VALUES (?,?,?,?,?)');
    $s->bind_param('idsss',$stID,$amount,$month,$status,$paidAt);
    $s->execute(); $newID=$db->insert_id; $s->close(); $db->close();
    success(['paymentID'=>$newID],'Payment recorded');
}
elseif ($method === 'PUT' && $action === 'approve' && $id) {
    requireAuth();
    $db=getDB(); $now=date('Y-m-d H:i:s');
    $s=$db->prepare('UPDATE payments SET status=\'approved\',paidAt=? WHERE paymentID=?');
    $s->bind_param('si',$now,$id); $s->execute(); $s->close(); $db->close();
    success([],'Payment approved');
}
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b=getBody();
    $amount=(float)($b['amount']??0); $status=$b['status']??'pending';
    $db=getDB();
    $s=$db->prepare('UPDATE payments SET amount=?,status=? WHERE paymentID=?');
    $s->bind_param('dsi',$amount,$status,$id); $s->execute(); $s->close(); $db->close();
    success([],'Payment updated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM payments WHERE paymentID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Payment deleted');
}
else { error('Unknown action',404); }
