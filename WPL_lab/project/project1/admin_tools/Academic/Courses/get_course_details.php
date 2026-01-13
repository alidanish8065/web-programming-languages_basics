<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$course_id = intval($_GET['id'] ?? 0);

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit;
}

try {
    // Fetch course details
    $sql = "
        SELECT 
            c.*,
            d.department_name,
            d.department_code,
            f.faculty_name
        FROM course c
        JOIN department d ON c.department_id = d.department_id
        JOIN faculty f ON d.faculty_id = f.faculty_id
        WHERE c.course_id = ? AND c.is_deleted = FALSE
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit;
    }
    
    // Fetch prerequisites
    $prereq_sql = "
        SELECT c.course_id, c.course_code, c.course_name, cp.is_mandatory
        FROM course_prerequisite cp
        JOIN course c ON cp.prerequisite_course_id = c.course_id
        WHERE cp.course_id = ?
        ORDER BY c.course_code
    ";
    $prereq_stmt = $conn->prepare($prereq_sql);
    $prereq_stmt->bind_param('i', $course_id);
    $prereq_stmt->execute();
    $prerequisites = $prereq_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'course' => $course,
        'prerequisites' => $prerequisites
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>