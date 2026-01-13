<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['notification_id'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid request']);
    exit;
}

$notification_id = intval($_POST['notification_id']);
$user_id = $_SESSION['user_id'];

// Allow deletion if user owns it or is admin
$user_role = $_SESSION['role'];
try {
    if (in_array($user_role,['admin','superadmin'])) {
        $stmt = $conn->prepare("DELETE FROM notification WHERE notification_id=?");
        $stmt->bind_param('i',$notification_id);
        $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM user_notification WHERE notification_id=?");
        $stmt->bind_param('i',$notification_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("DELETE FROM user_notification WHERE notification_id=? AND user_id=?");
        $stmt->bind_param('ii',$notification_id,$user_id);
        $stmt->execute();
    }
    echo json_encode(['success'=>true]);
} catch(Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
