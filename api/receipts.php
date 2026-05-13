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
        $s=$db->prepare('SELECT r.*,u.name AS studentName FROM receipts r JOIN payments p ON r.paymentID=p.paymentID JOIN students st ON p.studentID=st.studentID JOIN users u ON st.userID=u.userID WHERE p.studentID=? ORDER BY r.issueDate DESC');
        $s->bind_param('i',(int)$studentID); $s->execute();
        $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    } else {
        $rows=$db->query('SELECT r.*,u.name AS studentName FROM receipts r JOIN payments p ON r.paymentID=p.paymentID JOIN students st ON p.studentID=st.studentID JOIN users u ON st.userID=u.userID ORDER BY r.issueDate DESC')->fetch_all(MYSQLI_ASSOC);
    }
    $db->close(); success($rows);
}
elseif ($method === 'POST' && $action === 'generate') {
    requireAuth();
    $b=getBody();
    $payID=(int)($b['paymentID']??0); $amount=(float)($b['receiptAmount']??0); $note=$b['receiptNote']??'';
    if (!$payID||!$amount) error('Payment ID and amount required.');
    $db=getDB();
    $s=$db->prepare('INSERT INTO receipts (paymentID,receiptAmount,receiptNote) VALUES (?,?,?)');
    $s->bind_param('ids',$payID,$amount,$note);
    $s->execute(); $newID=$db->insert_id; $s->close(); $db->close();
    success(['receiptID'=>$newID],'Receipt generated');
}
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db=getDB();
    $s=$db->prepare('DELETE FROM receipts WHERE receiptID=?');
    $s->bind_param('i',$id); $s->execute(); $s->close(); $db->close();
    success([],'Receipt deleted');
}
else { error('Unknown action',404); }
