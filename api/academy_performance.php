<?php
require_once '../config/db.php';
setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ─── LIST ─────────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    requireAuth();
    $db   = getDB();
    $rows = $db->query('SELECT * FROM academy_performance ORDER BY reviewedAt DESC')->fetch_all(MYSQLI_ASSOC);
    $db->close();
    success($rows);
}

// ─── GET LATEST ───────────────────────────────────────────
elseif ($method === 'GET' && $action === 'latest') {
    requireAuth();
    $db  = getDB();
    $row = $db->query('SELECT * FROM academy_performance ORDER BY reviewedAt DESC LIMIT 1')->fetch_assoc();
    $db->close();
    if (!$row) error('No performance records found.', 404);
    success($row);
}

// ─── MONITOR / CREATE ─────────────────────────────────────
elseif ($method === 'POST' && $action === 'monitor') {
    requireAuth();
    $b      = getBody();
    $rating = (float)($b['overallRating']  ?? 0.0);
    $period = $b['reviewedPeriod'] ?? date('Y-m');

    $db = getDB();
    $s  = $db->prepare('INSERT INTO academy_performance (overallRating, reviewedPeriod) VALUES (?, ?)');
    $s->bind_param('ds', $rating, $period);
    $s->execute();
    $newID = $db->insert_id;
    $s->close(); $db->close();
    success(['performanceID' => $newID], 'Performance record added');
}

// ─── EDIT ─────────────────────────────────────────────────
elseif ($method === 'PUT' && $action === 'edit' && $id) {
    requireAuth();
    $b      = getBody();
    $rating = (float)($b['overallRating']  ?? 0.0);
    $period = $b['reviewedPeriod'] ?? '';

    $db = getDB();
    $s  = $db->prepare('UPDATE academy_performance SET overallRating=?, reviewedPeriod=? WHERE performanceID=?');
    $s->bind_param('dsi', $rating, $period, $id);
    $s->execute();
    $s->close(); $db->close();
    success([], 'Performance record updated');
}

// ─── DELETE ───────────────────────────────────────────────
elseif ($method === 'DELETE' && $action === 'delete' && $id) {
    requireAuth();
    $db = getDB();
    $s  = $db->prepare('DELETE FROM academy_performance WHERE performanceID=?');
    $s->bind_param('i', $id);
    $s->execute();
    $s->close(); $db->close();
    success([], 'Performance record deleted');
}

// ─── SUMMARY (aggregate stats from all tables) ────────────
elseif ($method === 'GET' && $action === 'summary') {
    requireAuth();
    $db = getDB();

    $students    = $db->query('SELECT COUNT(*) AS c FROM students')->fetch_assoc()['c'];
    $lecturers   = $db->query('SELECT COUNT(*) AS c FROM lecturers')->fetch_assoc()['c'];
    $batches     = $db->query('SELECT COUNT(*) AS c FROM batches')->fetch_assoc()['c'];
    $enrollments = $db->query("SELECT COUNT(*) AS c FROM enrollments WHERE status='active'")->fetch_assoc()['c'];
    $sessions    = $db->query('SELECT COUNT(*) AS c FROM class_sessions')->fetch_assoc()['c'];
    $completedS  = $db->query("SELECT COUNT(*) AS c FROM class_sessions WHERE sessionStatus='Completed'")->fetch_assoc()['c'];
    $payments    = $db->query('SELECT COUNT(*) AS c FROM payments')->fetch_assoc()['c'];
    $approvedP   = $db->query("SELECT COUNT(*) AS c FROM payments WHERE status='approved'")->fetch_assoc()['c'];
    $avgMarks    = $db->query('SELECT ROUND(AVG(marks),1) AS avg FROM exam_results')->fetch_assoc()['avg'];
    $avgFb       = $db->query('SELECT ROUND(AVG(feedbackRating),1) AS avg FROM feedback')->fetch_assoc()['avg'];
    $totalPts    = $db->query('SELECT COALESCE(SUM(points),0) AS s FROM performance_points')->fetch_assoc()['s'];

    // Attendance rate
    $attRow      = $db->query("SELECT COUNT(*) AS total, SUM(status='present') AS present FROM attendance")->fetch_assoc();
    $attRate     = $attRow['total'] > 0 ? round($attRow['present'] / $attRow['total'] * 100) : 0;

    $db->close();
    success([
        'students'          => (int)$students,
        'lecturers'         => (int)$lecturers,
        'batches'           => (int)$batches,
        'activeEnrollments' => (int)$enrollments,
        'totalSessions'     => (int)$sessions,
        'completedSessions' => (int)$completedS,
        'totalPayments'     => (int)$payments,
        'approvedPayments'  => (int)$approvedP,
        'averageMarks'      => (float)($avgMarks ?? 0),
        'averageFeedback'   => (float)($avgFb ?? 0),
        'totalPoints'       => (int)$totalPts,
        'attendanceRate'    => $attRate,
    ]);
}

else { error('Unknown action', 404); }
