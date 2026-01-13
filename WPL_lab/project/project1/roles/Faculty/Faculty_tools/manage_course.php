<?php
/**
 * Manage Course - Redirect to modular structure
 * 
 * This file now redirects to the new modular implementation in Manage_courses/
 * The monolithic code has been refactored into:
 *   - Services: ModuleService, LessonService, AssignmentService, GradingService, CourseDataService
 *   - Controller: ManageCourseController
 *   - Views: course_list.php, course_dashboard.php
 *   - Partials: module_card.php, lesson_form.php, assignment_form.php, module_modal.php
 */

// Preserve query string when redirecting
$queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: Manage_courses/index.php' . $queryString);
exit;