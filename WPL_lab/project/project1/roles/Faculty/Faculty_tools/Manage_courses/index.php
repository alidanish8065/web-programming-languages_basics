<?php
/**
 * Manage Courses - Entry Point
 * Routes to the ManageCourseController
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

// Auth check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'teacher'])) {
    header('Location: ' . url('public/login.php'));
    exit;
}

// Load and execute controller
require_once __DIR__ . '/controller.php';
$controller = new ManageCourseController($conn);
$controller->handle();

$conn->close();
