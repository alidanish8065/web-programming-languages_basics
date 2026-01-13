<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$offering_id = (int)$_GET['id'];

try {
    // Check if students are enrolled
    $check = $conn->prepare("SELECT COUNT(*) as count FROM enrollment WHERE offering_id = ? AND status = 'enrolled'");
    $check->bind_param('i', $offering_id);
    $check->execute();
    $enrolled = $check->get_result()->fetch_assoc()['count'];
    
    if ($enrolled > 0) {
        $_SESSION['error'] = "Cannot delete offering with enrolled students!";
    } else {
        $stmt = $conn->prepare("DELETE FROM course_offering WHERE offering_id = ?");
        $stmt->bind_param('i', $offering_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Offering deleted successfully!";
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: offering_list.php');
exit;
?>