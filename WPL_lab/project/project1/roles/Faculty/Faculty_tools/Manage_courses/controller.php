<?php
/**
 * ManageCourseController - Main controller for course management
 */
require_once __DIR__ . '/services/ModuleService.php';
require_once __DIR__ . '/services/LessonService.php';
require_once __DIR__ . '/services/AssignmentService.php';
require_once __DIR__ . '/services/GradingService.php';
require_once __DIR__ . '/services/CourseDataService.php';

class ManageCourseController {
    private mysqli $db;
    private int $teacherId;
    private string $msg = '';
    private string $error = '';

    public function __construct(mysqli $db) {
        $this->db = $db;
        $this->teacherId = $_SESSION['user_id'];
    }

    /**
     * Main entry point
     */
    public function handle(): void {
        $offeringId = $_GET['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($offeringId);
        }

        if (!$offeringId) {
            $this->showCourseList();
        } else {
            $this->showCourseDashboard($offeringId);
        }
    }

    /**
     * Handle all POST actions
     */
    private function handlePost(?int $offeringId): void {
        if (isset($_POST['create_module']) && $offeringId) {
            if ((new ModuleService($this->db))->create($_POST, $offeringId)) {
                $this->msg = "Module created successfully!";
            } else {
                $this->error = "Failed to create module.";
            }
        }

        if (isset($_POST['create_lesson'])) {
            if ((new LessonService($this->db))->create($_POST, $_FILES, $this->teacherId)) {
                $this->msg = "Lesson created with resources!";
            } else {
                $this->error = "Failed to create lesson.";
            }
        }

        if (isset($_POST['create_assignment'])) {
            if ((new AssignmentService($this->db))->create($_POST, $_FILES, $this->teacherId)) {
                $this->msg = "Assignment created successfully!";
            } else {
                $this->error = "Failed to create assignment.";
            }
        }

        if (isset($_POST['grade_submission'])) {
            $subId = (int)$_POST['submission_id'];
            $marks = (int)$_POST['marks'];
            $feedback = trim($_POST['feedback'] ?? '');
            
            if ((new GradingService($this->db))->gradeSubmission($subId, $marks, $feedback, $this->teacherId)) {
                $this->msg = "Submission graded successfully!";
            } else {
                $this->error = "Failed to grade submission.";
            }
        }

        if (isset($_POST['delete_lesson'])) {
            if ((new LessonService($this->db))->delete((int)$_POST['lesson_id'])) {
                $this->msg = "Lesson deleted!";
            }
        }

        if (isset($_POST['delete_assignment'])) {
            if ((new AssignmentService($this->db))->delete((int)$_POST['assignment_id'])) {
                $this->msg = "Assignment deleted!";
            }
        }
    }

    /**
     * Show list of teacher's courses
     */
    private function showCourseList(): void {
        $courseDataService = new CourseDataService($this->db);
        $courses = $courseDataService->getTeacherCourses($this->teacherId);
        $conn = $this->db; // Pass to views for navbar/layout
        
        require __DIR__ . '/views/course_list.php';
    }

    /**
     * Show course dashboard with modules, lessons, assignments
     */
    private function showCourseDashboard(int $offeringId): void {
        $courseDataService = new CourseDataService($this->db);
        
        // Verify access
        $course = $courseDataService->verifyCourseAccess($offeringId, $this->teacherId);
        if (!$course) {
            die('Access denied or course not found.');
        }
        
        // Get all modules with content
        $modules = $courseDataService->getModulesWithContent($offeringId);
        $nextModuleSeq = $courseDataService->getNextModuleSequence($modules);
        
        // Pass data to view
        $msg = $this->msg;
        $error = $this->error;
        $gradingService = new GradingService($this->db);
        $conn = $this->db; // Pass to views for navbar/layout
        
        require __DIR__ . '/views/course_dashboard.php';
    }

    // Getters for view access
    public function getMsg(): string { return $this->msg; }
    public function getError(): string { return $this->error; }
}
