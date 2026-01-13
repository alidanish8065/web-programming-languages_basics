-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2026 at 05:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendar`
--

CREATE TABLE `academic_calendar` (
  `calendar_id` int(11) NOT NULL,
  `academic_year` varchar(9) NOT NULL COMMENT 'e.g., 2025-2026',
  `semester` enum('1','2','3','4','5','6','7','8') NOT NULL,
  `term` enum('Fall','Spring','Summer') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `registration_start_date` date DEFAULT NULL,
  `registration_end_date` date DEFAULT NULL,
  `add_drop_deadline` date DEFAULT NULL,
  `midterm_start_date` date DEFAULT NULL,
  `midterm_end_date` date DEFAULT NULL,
  `final_exam_start_date` date DEFAULT NULL,
  `final_exam_end_date` date DEFAULT NULL,
  `status` enum('draft','active','completed','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `admission`
--

CREATE TABLE `admission` (
  `admission_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `application_date` date NOT NULL DEFAULT curdate(),
  `status` enum('applied','under_review','accepted','rejected','deferred','withdrawn') NOT NULL DEFAULT 'applied',
  `applied_semester` enum('Fall','Spring','Summer') DEFAULT NULL,
  `admission_year` year(4) NOT NULL,
  `remarks` text DEFAULT NULL,
  `submitted_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`submitted_documents`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_document`
--

CREATE TABLE `admission_document` (
  `admission_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_status_log`
--

CREATE TABLE `admission_status_log` (
  `log_id` bigint(20) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `old_status` enum('applied','under_review','accepted','rejected','deferred','withdrawn') DEFAULT NULL,
  `new_status` enum('applied','under_review','accepted','rejected','deferred','withdrawn') NOT NULL,
  `status_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `documents_verified` tinyint(1) DEFAULT NULL,
  `interview_conducted` tinyint(1) DEFAULT NULL,
  `test_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_weight`
--

CREATE TABLE `assessment_weight` (
  `offering_id` int(11) NOT NULL,
  `assessment_type` enum('assignment','exam') NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `weightage` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `assignment_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `assignment_title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `max_marks` int(11) NOT NULL,
  `weightage` decimal(5,2) NOT NULL,
  `due_date` datetime NOT NULL,
  `allow_late_submission` tinyint(1) NOT NULL DEFAULT 0,
  `late_penalty_percent` decimal(5,2) DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment`
--

INSERT INTO `assignment` (`assignment_id`, `module_id`, `assignment_title`, `description`, `max_marks`, `weightage`, `due_date`, `allow_late_submission`, `late_penalty_percent`, `status`, `created_at`, `updated_at`, `updated_by`, `deleted_at`) VALUES
(2, 1, 'assignment 1', 'do it', 5, 20.00, '2026-01-06 20:41:00', 0, NULL, 'published', '2026-01-05 15:41:49', '2026-01-05 15:41:49', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assignment_resource`
--

CREATE TABLE `assignment_resource` (
  `assignment_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_resource`
--

INSERT INTO `assignment_resource` (`assignment_id`, `resource_id`, `is_primary`) VALUES
(2, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submission`
--

CREATE TABLE `assignment_submission` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `submission_resource_id` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `penalty_applied_percent` decimal(5,2) DEFAULT NULL,
  `marks_obtained` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('draft','submitted','graded','resubmitted') NOT NULL DEFAULT 'draft',
  `graded_by` int(11) DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_change_log`
--

CREATE TABLE `attendance_change_log` (
  `log_id` bigint(20) NOT NULL,
  `record_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `old_status` enum('present','absent','late','excused') DEFAULT NULL,
  `new_status` enum('present','absent','late','excused') NOT NULL,
  `old_remarks` varchar(255) DEFAULT NULL,
  `new_remarks` varchar(255) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_record`
--

CREATE TABLE `attendance_record` (
  `record_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attendance_status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_record`
--

INSERT INTO `attendance_record` (`record_id`, `session_id`, `student_id`, `attendance_status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 15, 'present', '', '2026-01-05 15:07:43', '2026-01-05 15:07:43');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_session`
--

CREATE TABLE `attendance_session` (
  `session_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `offering_id` int(11) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `session_type` enum('lecture','lab','practical','tutorial') NOT NULL DEFAULT 'lecture',
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_session`
--

INSERT INTO `attendance_session` (`session_id`, `lesson_id`, `offering_id`, `session_date`, `start_time`, `end_time`, `session_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, NULL, '2026-01-05', '00:00:00', NULL, 'lecture', 'scheduled', '2026-01-05 15:07:37', '2026-01-05 15:07:37');

-- --------------------------------------------------------

--
-- Table structure for table `bulk_operation_log`
--

CREATE TABLE `bulk_operation_log` (
  `log_id` bigint(20) NOT NULL,
  `operation_type` enum('bulk_enrollment','bulk_grade_update','bulk_user_create','bulk_email','bulk_delete','bulk_status_change','bulk_import','bulk_export') NOT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `initiated_by` int(11) DEFAULT NULL,
  `total_records` int(11) NOT NULL DEFAULT 0,
  `successful_records` int(11) NOT NULL DEFAULT 0,
  `failed_records` int(11) NOT NULL DEFAULT 0,
  `status` enum('initiated','processing','completed','failed','partial') NOT NULL DEFAULT 'initiated',
  `operation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Details of the operation' CHECK (json_valid(`operation_data`)),
  `error_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Failed record details' CHECK (json_valid(`error_log`)),
  `initiated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campus`
--

CREATE TABLE `campus` (
  `campus_id` int(11) NOT NULL,
  `campus_name` varchar(100) NOT NULL,
  `campus_code` varchar(20) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campus`
--

INSERT INTO `campus` (`campus_id`, `campus_name`, `campus_code`, `location`, `address`, `contact_number`, `email`, `status`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 'North Campus', 'C-001', 'abc-123', 'plot-xyz , area abc', '111-222-333', 'north_university@gmail.com', 'active', 0, '2025-12-26 20:43:10', '2025-12-26 20:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `certificate`
--

CREATE TABLE `certificate` (
  `certificate_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `certificate_type` enum('course_completion','program_graduation','participation','honors') NOT NULL DEFAULT 'course_completion',
  `certificate_name` varchar(150) NOT NULL,
  `issued_at` date NOT NULL DEFAULT curdate(),
  `expiration_date` date DEFAULT NULL,
  `certificate_resource_id` int(11) DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_change_log`
--

CREATE TABLE `content_change_log` (
  `log_id` bigint(20) NOT NULL,
  `content_type` enum('module','lesson','assignment','exam','resource') NOT NULL,
  `content_id` int(11) NOT NULL,
  `action` enum('created','updated','deleted','published','unpublished','archived') NOT NULL,
  `field_changed` varchar(100) DEFAULT NULL COMMENT 'Which field was modified',
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_summary` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `credit_hrs` int(11) NOT NULL,
  `course_type` enum('core','elective') NOT NULL,
  `recommended_semester` int(11) DEFAULT NULL COMMENT 'Recommended semester for the course',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `department_id`, `course_code`, `course_name`, `credit_hrs`, `course_type`, `recommended_semester`, `description`, `created_at`, `updated_at`, `is_deleted`, `updated_by`) VALUES
(2, 2, 'CS-101', 'Introduction to programming', 3, 'core', 1, 'Basics of the Computer programming logic', '2025-12-28 12:20:14', '2025-12-28 12:20:14', 0, NULL),
(3, 2, 'CS-102', 'Object Oriented Programming', 3, 'core', 2, 'Object based programming', '2025-12-28 12:21:46', '2025-12-28 12:21:46', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_evaluation`
--

CREATE TABLE `course_evaluation` (
  `evaluation_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `content_quality_rating` int(11) DEFAULT NULL CHECK (`content_quality_rating` between 1 and 5),
  `instructor_rating` int(11) DEFAULT NULL CHECK (`instructor_rating` between 1 and 5),
  `learning_outcome_rating` int(11) DEFAULT NULL CHECK (`learning_outcome_rating` between 1 and 5),
  `course_difficulty_rating` int(11) DEFAULT NULL CHECK (`course_difficulty_rating` between 1 and 5),
  `overall_rating` int(11) DEFAULT NULL CHECK (`overall_rating` between 1 and 5),
  `strengths` text DEFAULT NULL,
  `improvements` text DEFAULT NULL,
  `additional_comments` text DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 1,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_offering`
--

CREATE TABLE `course_offering` (
  `offering_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` enum('1','2','3','4','5','6','7','8','9','10') NOT NULL,
  `term` enum('Fall','Spring','Summer') NOT NULL DEFAULT 'Fall',
  `max_enrollment` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_offering`
--

INSERT INTO `course_offering` (`offering_id`, `course_id`, `academic_year`, `semester`, `term`, `max_enrollment`, `location`, `created_at`, `updated_at`) VALUES
(1, 3, '2025-2026', '2', 'Fall', 120, '301', '2025-12-31 13:40:31', '2025-12-31 13:40:31'),
(2, 2, '2025-2026', '1', 'Fall', NULL, '301', '2025-12-31 21:09:29', '2025-12-31 21:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `course_prerequisite`
--

CREATE TABLE `course_prerequisite` (
  `course_id` int(11) NOT NULL,
  `prerequisite_course_id` int(11) NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `course_prerequisite`
--

INSERT INTO `course_prerequisite` (`course_id`, `prerequisite_course_id`, `is_mandatory`) VALUES
(3, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `course_section`
--

CREATE TABLE `course_section` (
  `section_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `section_name` varchar(10) NOT NULL COMMENT 'e.g., A, B, C or 01, 02',
  `max_enrollment` int(11) DEFAULT NULL,
  `current_enrollment` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `course_teacher`
--

CREATE TABLE `course_teacher` (
  `course_teacher_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `role` enum('instructor','co_instructor','lab_instructor','teaching_assistant') NOT NULL DEFAULT 'instructor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_teacher`
--

INSERT INTO `course_teacher` (`course_teacher_id`, `offering_id`, `teacher_id`, `role`, `created_at`, `updated_at`) VALUES
(1, 1, 18, 'teaching_assistant', '2025-12-31 13:41:04', '2025-12-31 13:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `database_change_log`
--

CREATE TABLE `database_change_log` (
  `log_id` bigint(20) NOT NULL,
  `change_type` enum('CREATE','ALTER','DROP','TRUNCATE','RENAME') NOT NULL,
  `object_type` enum('TABLE','INDEX','CONSTRAINT','TRIGGER','VIEW','PROCEDURE') NOT NULL,
  `object_name` varchar(100) NOT NULL,
  `sql_statement` text DEFAULT NULL,
  `executed_by` int(11) DEFAULT NULL,
  `execution_status` enum('success','failed','rolled_back') NOT NULL,
  `error_message` text DEFAULT NULL,
  `backup_taken` tinyint(1) NOT NULL DEFAULT 0,
  `rollback_script` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_export_log`
--

CREATE TABLE `data_export_log` (
  `log_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `export_type` enum('student_records','grades','attendance','financial','course_data','user_data','reports','bulk_export') NOT NULL,
  `export_format` enum('csv','excel','pdf','json','xml') NOT NULL,
  `filters_applied` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Export criteria/filters' CHECK (json_valid(`filters_applied`)),
  `record_count` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `status` enum('initiated','processing','completed','failed') NOT NULL DEFAULT 'initiated',
  `initiated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(50) NOT NULL,
  `department_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `faculty_id` int(11) NOT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `department_code`, `department_status`, `email`, `contact_number`, `faculty_id`, `campus_id`, `created_at`, `updated_at`, `is_deleted`, `updated_by`) VALUES
(2, 'Computer Science', 'CS-001', 'active', '', NULL, 1, 1, '2025-12-26 20:43:48', '2025-12-26 20:43:48', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `enrollment_date` date NOT NULL DEFAULT curdate(),
  `status` enum('enrolled','dropped','completed','failed') NOT NULL DEFAULT 'enrolled',
  `grade_point` decimal(3,2) DEFAULT NULL,
  `grade` char(2) GENERATED ALWAYS AS (case when `grade_point` >= 3.7 then 'A' when `grade_point` >= 3.3 then 'A-' when `grade_point` >= 3.0 then 'B+' when `grade_point` >= 2.7 then 'B' when `grade_point` >= 2.3 then 'B-' when `grade_point` >= 2.0 then 'C+' when `grade_point` >= 1.7 then 'C' when `grade_point` >= 1.3 then 'C-' when `grade_point` >= 1.0 then 'D' else 'F' end) STORED,
  `credit_hrs` int(11) NOT NULL,
  `credit_earned` int(11) GENERATED ALWAYS AS (case when `status` = 'completed' then `credit_hrs` else 0 end) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`enrollment_id`, `student_id`, `offering_id`, `section_id`, `enrollment_date`, `status`, `grade_point`, `credit_hrs`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 15, 1, NULL, '2025-12-31', 'enrolled', NULL, 3, '2025-12-31 13:41:33', '2025-12-31 13:41:33', NULL),
(2, 15, 2, NULL, '2026-01-05', 'enrolled', NULL, 3, '2026-01-05 14:33:58', '2026-01-05 14:33:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_log`
--

CREATE TABLE `enrollment_log` (
  `log_id` bigint(20) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `action` enum('created','updated','status_changed','grade_changed','dropped','withdrawn') NOT NULL,
  `old_status` enum('enrolled','dropped','completed','failed') DEFAULT NULL,
  `new_status` enum('enrolled','dropped','completed','failed') DEFAULT NULL,
  `old_grade_point` decimal(3,2) DEFAULT NULL,
  `new_grade_point` decimal(3,2) DEFAULT NULL,
  `old_grade` char(2) DEFAULT NULL,
  `new_grade` char(2) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_withdrawal`
--

CREATE TABLE `enrollment_withdrawal` (
  `withdrawal_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `withdrawal_date` date NOT NULL DEFAULT curdate(),
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `refund_percentage` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `exam_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `exam_title` varchar(150) NOT NULL,
  `exam_type` enum('quiz','midterm','final','makeup','practical') NOT NULL,
  `max_marks` int(11) NOT NULL,
  `weightage` decimal(5,2) NOT NULL,
  `exam_mode` enum('online','physical','hybrid') NOT NULL,
  `scheduled_start` datetime NOT NULL,
  `scheduled_end` datetime NOT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempt`
--

CREATE TABLE `exam_attempt` (
  `attempt_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `total_marks_obtained` decimal(6,2) DEFAULT NULL,
  `status` enum('registered','absent','submitted','evaluated','cancelled') NOT NULL DEFAULT 'registered',
  `graded_by` int(11) DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_integrity_log`
--

CREATE TABLE `exam_integrity_log` (
  `log_id` bigint(20) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `event_type` enum('exam_started','exam_paused','exam_resumed','tab_switched','window_switched','browser_closed','copy_detected','paste_detected','screenshot_attempt','suspicious_activity','exam_submitted','exam_auto_submitted') NOT NULL,
  `event_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional event metadata' CHECK (json_valid(`event_details`)),
  `timestamp_event` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_resource`
--

CREATE TABLE `exam_resource` (
  `exam_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_section`
--

CREATE TABLE `exam_section` (
  `section_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `section_title` varchar(100) NOT NULL,
  `max_marks` int(11) NOT NULL,
  `sequence_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_section_mark`
--

CREATE TABLE `exam_section_mark` (
  `attempt_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `marks_obtained` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `faculty_name` varchar(100) NOT NULL,
  `faculty_code` varchar(50) NOT NULL,
  `faculty_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `faculty_name`, `faculty_code`, `faculty_status`, `is_deleted`) VALUES
(1, 'Computer Science', 'FSC-001', 'active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure`
--

CREATE TABLE `fee_structure` (
  `program_id` int(11) NOT NULL,
  `fee_type` enum('tuition','lab','library','sports','misc') NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_access_log`
--

CREATE TABLE `file_access_log` (
  `log_id` bigint(20) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `access_type` enum('view','download','upload','delete','share') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL COMMENT 'Size in bytes',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum`
--

CREATE TABLE `forum` (
  `forum_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_post`
--

CREATE TABLE `forum_post` (
  `post_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `parent_post_id` int(11) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_edited` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_post_resource`
--

CREATE TABLE `forum_post_resource` (
  `post_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_thread`
--

CREATE TABLE `forum_thread` (
  `thread_id` int(11) NOT NULL,
  `forum_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','closed','archived') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_appeal`
--

CREATE TABLE `grade_appeal` (
  `appeal_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `appeal_reason` text NOT NULL,
  `original_grade` char(2) DEFAULT NULL,
  `requested_grade` char(2) DEFAULT NULL,
  `status` enum('submitted','under_review','approved','rejected') NOT NULL DEFAULT 'submitted',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `final_grade` char(2) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_change_log`
--

CREATE TABLE `grade_change_log` (
  `log_id` bigint(20) NOT NULL,
  `record_type` enum('enrollment','assignment','exam') NOT NULL,
  `record_id` int(11) NOT NULL COMMENT 'ID of enrollment/submission/exam_attempt',
  `student_id` int(11) NOT NULL,
  `old_marks` decimal(6,2) DEFAULT NULL,
  `new_marks` decimal(6,2) DEFAULT NULL,
  `old_grade_point` decimal(3,2) DEFAULT NULL,
  `new_grade_point` decimal(3,2) DEFAULT NULL,
  `old_grade` varchar(5) DEFAULT NULL,
  `new_grade` varchar(5) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text NOT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_component`
--

CREATE TABLE `grade_component` (
  `component_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `component_name` varchar(100) NOT NULL COMMENT 'e.g., Assignments, Midterm, Final, Quizzes',
  `component_type` enum('assignment','exam','project','participation','other') NOT NULL DEFAULT 'other',
  `weightage` decimal(5,2) NOT NULL COMMENT 'Percentage contribution to final grade',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `invoice_date` date NOT NULL DEFAULT curdate(),
  `due_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','partially_paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson`
--

CREATE TABLE `lesson` (
  `lesson_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `lesson_title` varchar(150) NOT NULL,
  `delivery_mode` enum('live','async') NOT NULL DEFAULT 'async',
  `content_type` enum('video','text','slides','mixed') NOT NULL DEFAULT 'mixed',
  `lesson_type` enum('video','live','text','slides') NOT NULL,
  `description` text DEFAULT NULL,
  `sequence_number` int(11) NOT NULL,
  `scheduled_start` datetime DEFAULT NULL,
  `scheduled_end` datetime DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson`
--

INSERT INTO `lesson` (`lesson_id`, `module_id`, `lesson_title`, `delivery_mode`, `content_type`, `lesson_type`, `description`, `sequence_number`, `scheduled_start`, `scheduled_end`, `status`, `created_at`, `updated_at`) VALUES
(6, 1, 'Lecture 1', 'async', 'mixed', 'video', '', 1, NULL, NULL, 'published', '2026-01-05 15:06:12', '2026-01-05 15:06:12');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_resource`
--

CREATE TABLE `lesson_resource` (
  `lesson_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson_resource`
--

INSERT INTO `lesson_resource` (`lesson_id`, `resource_id`, `is_primary`) VALUES
(6, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `log_retention_policy`
--

CREATE TABLE `log_retention_policy` (
  `policy_id` int(11) NOT NULL,
  `log_table_name` varchar(100) NOT NULL,
  `retention_days` int(11) NOT NULL COMMENT 'Days to keep logs',
  `archive_before_delete` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_cleanup_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_retention_policy`
--

INSERT INTO `log_retention_policy` (`policy_id`, `log_table_name`, `retention_days`, `archive_before_delete`, `is_active`, `last_cleanup_at`, `created_at`, `updated_at`) VALUES
(1, 'user_activity_log', 365, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(2, 'enrollment_log', 1825, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(3, 'grade_change_log', 2555, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(4, 'payment_transaction_log', 2555, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(5, 'attendance_change_log', 1095, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(6, 'student_status_log', 1825, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(7, 'content_change_log', 730, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(8, 'admission_status_log', 1825, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(9, 'system_config_log', 1095, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(10, 'role_permission_log', 1095, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(11, 'file_access_log', 180, 0, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(12, 'notification_delivery_log', 90, 0, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(13, 'exam_integrity_log', 1825, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(14, 'data_export_log', 730, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(15, 'bulk_operation_log', 365, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(16, 'security_incident_log', 1095, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30'),
(17, 'database_change_log', 1825, 1, 1, NULL, '2025-12-24 20:52:30', '2025-12-24 20:52:30');

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

CREATE TABLE `module` (
  `module_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `module_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `sequence_number` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `module`
--

INSERT INTO `module` (`module_id`, `offering_id`, `module_name`, `module_code`, `description`, `sequence_number`, `status`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Introduction to programming', 'M01', 'Introduction to programming', 1, 'active', '2025-12-31', '2026-04-30', '2025-12-31 16:57:49', '2025-12-31 16:57:49');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('info','warning','alert','assignment','exam','system') NOT NULL DEFAULT 'info',
  `is_general` tinyint(1) NOT NULL DEFAULT 0,
  `scheduled_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notification_id`, `title`, `message`, `notification_type`, `is_general`, `scheduled_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PAKISTAN DAY HOLIDAY !', 'university will stay off on the occasion of pakistan day', 'info', 1, '2025-12-30 22:23:00', 12, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(2, 'Exam alert', '22 decmeber fall examination start', 'alert', 0, '2026-02-22 22:59:00', 12, '2025-12-30 17:59:55', '2025-12-30 17:59:55'),
(3, 'hello', 'hello', 'alert', 0, '2026-01-01 01:00:00', 12, '2025-12-31 20:01:15', '2025-12-31 20:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `notification_delivery_log`
--

CREATE TABLE `notification_delivery_log` (
  `log_id` bigint(20) NOT NULL,
  `notification_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `delivery_method` enum('email','sms','push','in_app') NOT NULL,
  `recipient_address` varchar(255) DEFAULT NULL COMMENT 'Email or phone number',
  `subject` varchar(255) DEFAULT NULL,
  `status` enum('queued','sent','delivered','failed','bounced','opened','clicked') NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `provider` varchar(100) DEFAULT NULL COMMENT 'Email/SMS service provider',
  `provider_message_id` varchar(255) DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_queue`
--

CREATE TABLE `notification_queue` (
  `queue_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_method` enum('email','sms','push','in_app') NOT NULL DEFAULT 'in_app',
  `scheduled_for` datetime DEFAULT NULL COMMENT 'When to send the notification',
  `sent_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_queue`
--

INSERT INTO `notification_queue` (`queue_id`, `notification_id`, `user_id`, `status`, `delivery_method`, `scheduled_for`, `sent_at`, `failed_at`, `failure_reason`, `retry_count`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'pending', 'in_app', '2025-12-30 22:23:00', NULL, NULL, NULL, 0, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(2, 1, 6, 'pending', 'in_app', '2025-12-30 22:23:00', NULL, NULL, NULL, 0, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(3, 1, 8, 'pending', 'in_app', '2025-12-30 22:23:00', NULL, NULL, NULL, 0, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(4, 1, 12, 'pending', 'in_app', '2025-12-30 22:23:00', NULL, NULL, NULL, 0, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(5, 1, 13, 'pending', 'in_app', '2025-12-30 22:23:00', NULL, NULL, NULL, 0, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(6, 1, 15, 'pending', 'in_app', '2025-12-30 22:23:00', NULL, NULL, NULL, 0, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(7, 1, 16, 'pending', 'in_app', '2025-12-30 22:23:00', NULL, NULL, NULL, 0, '2025-12-30 17:24:01', '2025-12-30 17:24:01'),
(8, 2, 6, 'pending', 'in_app', '2026-02-22 22:59:00', NULL, NULL, NULL, 0, '2025-12-30 17:59:55', '2025-12-30 17:59:55'),
(9, 2, 8, 'pending', 'in_app', '2026-02-22 22:59:00', NULL, NULL, NULL, 0, '2025-12-30 17:59:55', '2025-12-30 17:59:55'),
(10, 2, 15, 'pending', 'in_app', '2026-02-22 22:59:00', NULL, NULL, NULL, 0, '2025-12-30 17:59:55', '2025-12-30 17:59:55'),
(11, 3, 6, 'pending', 'in_app', '2026-01-01 01:00:00', NULL, NULL, NULL, 0, '2025-12-31 20:01:15', '2025-12-31 20:01:15'),
(12, 3, 8, 'pending', 'in_app', '2026-01-01 01:00:00', NULL, NULL, NULL, 0, '2025-12-31 20:01:15', '2025-12-31 20:01:15'),
(13, 3, 15, 'pending', 'in_app', '2026-01-01 01:00:00', NULL, NULL, NULL, 0, '2025-12-31 20:01:15', '2025-12-31 20:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `notification_resource`
--

CREATE TABLE `notification_resource` (
  `notification_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cash','bank_transfer','card','online','cheque') NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transaction_log`
--

CREATE TABLE `payment_transaction_log` (
  `log_id` bigint(20) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `transaction_type` enum('payment_initiated','payment_completed','payment_failed','refund_initiated','refund_completed','refund_failed','payment_cancelled','payment_reversed') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','card','online','cheque') DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `status` enum('success','pending','failed','cancelled') NOT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `program_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `program_code` varchar(50) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `degree_level` enum('diploma','bachelors','masters','phd') NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in years',
  `minimum_semesters` int(11) NOT NULL,
  `minimum_credit_hrs` int(11) NOT NULL,
  `program_status` enum('active','inactive','phased_out') NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program`
--

INSERT INTO `program` (`program_id`, `department_id`, `program_code`, `program_name`, `degree_level`, `duration`, `minimum_semesters`, `minimum_credit_hrs`, `program_status`, `description`, `created_at`, `updated_at`, `is_deleted`, `updated_by`) VALUES
(1, 2, 'BCS-001', 'Bachelors Of Computer Sciences', 'bachelors', 4, 8, 132, 'active', '4 year plan for bachelors', '2025-12-26 20:46:13', '2025-12-26 20:49:45', 0, NULL),
(3, 2, 'MS(CS)', 'Master in Computer Science', '', 2, 4, 60, 'active', 'Master in computer Science', '2025-12-28 12:32:06', '2025-12-28 12:32:06', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `program_course`
--

CREATE TABLE `program_course` (
  `program_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'TRUE for core, FALSE for elective',
  `recommended_semester` int(11) DEFAULT NULL COMMENT 'Recommended semester number'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource`
--

CREATE TABLE `resource` (
  `resource_id` int(11) NOT NULL,
  `resource_type` enum('video','pdf','ppt','doc','link','image','audio') NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `storage_provider` enum('local','s3','gcs','cdn') NOT NULL DEFAULT 'local',
  `version` int(11) NOT NULL DEFAULT 1,
  `resource_url` varchar(2048) NOT NULL COMMENT 'Supports longer URLs',
  `resource_name` varchar(150) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resource`
--

INSERT INTO `resource` (`resource_id`, `resource_type`, `mime_type`, `file_size`, `storage_provider`, `version`, `resource_url`, `resource_name`, `uploaded_by`, `created_at`) VALUES
(1, 'ppt', '', 0, 'local', 1, 'public/uploads/resources/res_1767625109_695bd1952e70b.pptx', 'Time-Management-and-Productivity.pptx', 18, '2026-01-05 14:58:29'),
(2, 'pdf', '', 0, 'local', 1, 'public/uploads/resources/res_1767625572_695bd3647d5c8.pptx', 'Time-Management-and-Productivity.pptx', 18, '2026-01-05 15:06:12'),
(3, 'doc', '', 0, 'local', 1, 'public/uploads/resources/assign_1767627710_695bdbbed5075.pptx', 'Time-Management-and-Productivity.pptx', 18, '2026-01-05 15:41:51');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` enum('student','faculty','admin','student_affairs','examination','admission','accounts') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'student'),
(2, 'faculty'),
(3, 'admin'),
(4, 'student_affairs'),
(5, 'examination'),
(6, 'admission'),
(7, 'accounts');

-- --------------------------------------------------------

--
-- Table structure for table `roles_permissions`
--

CREATE TABLE `roles_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_change_requests`
--

CREATE TABLE `role_change_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `new_role_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permission_log`
--

CREATE TABLE `role_permission_log` (
  `log_id` bigint(20) NOT NULL,
  `change_type` enum('role_assigned','role_removed','permission_granted','permission_revoked') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL,
  `old_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Previous roles array' CHECK (json_valid(`old_roles`)),
  `new_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'New roles array' CHECK (json_valid(`new_roles`)),
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `room_type` enum('classroom','lab','virtual','auditorium') NOT NULL DEFAULT 'classroom',
  `location` varchar(150) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_availability`
--

CREATE TABLE `room_availability` (
  `room_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_incident_log`
--

CREATE TABLE `security_incident_log` (
  `log_id` bigint(20) NOT NULL,
  `incident_type` enum('unauthorized_access','brute_force_attempt','sql_injection_attempt','xss_attempt','csrf_attempt','privilege_escalation','data_breach_attempt','suspicious_activity','account_lockout') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_url` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_payload` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `action_taken` text DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `admission_year` year(4) NOT NULL,
  `current_semester` int(11) NOT NULL DEFAULT 1,
  `enrollment_status` enum('active','suspended','withdrawn','graduated','expelled','on_break') NOT NULL DEFAULT 'active',
  `academic_standing` enum('good','probation','warning','honors') DEFAULT 'good',
  `attempted_credit_hrs` int(11) NOT NULL DEFAULT 0,
  `completed_credit_hrs` int(11) NOT NULL DEFAULT 0,
  `remaining_credit_hrs` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cgpa` decimal(3,2) DEFAULT NULL COMMENT 'Cumulative Grade Point Average',
  `sgpa` decimal(3,2) DEFAULT NULL COMMENT 'Semester Grade Point Average'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `program_id`, `student_number`, `admission_year`, `current_semester`, `enrollment_status`, `academic_standing`, `attempted_credit_hrs`, `completed_credit_hrs`, `remaining_credit_hrs`, `created_at`, `updated_at`, `cgpa`, `sgpa`) VALUES
(8, 1, 'BCS-001-2025-0002', '2025', 1, 'active', 'good', 0, 0, 132, '2025-12-26 21:13:36', '2025-12-26 21:13:36', NULL, NULL),
(15, 1, 'BCS-001-2025-0003', '2025', 1, 'active', 'good', 0, 0, 132, '2025-12-28 13:05:25', '2025-12-28 13:05:25', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_number_sequence`
--

CREATE TABLE `student_number_sequence` (
  `id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `program_code` varchar(10) NOT NULL,
  `last_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_number_sequence`
--

INSERT INTO `student_number_sequence` (`id`, `year`, `program_code`, `last_number`, `created_at`, `updated_at`) VALUES
(1, '2025', 'BCS-001', 3, '2025-12-26 20:50:54', '2025-12-28 13:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `student_program_change_requests`
--

CREATE TABLE `student_program_change_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `new_program_id` int(11) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_status_log`
--

CREATE TABLE `student_status_log` (
  `log_id` bigint(20) NOT NULL,
  `student_id` int(11) NOT NULL,
  `change_type` enum('enrollment_status','academic_standing','program_change','semester_progression','gpa_update','credit_hours_update') NOT NULL,
  `old_enrollment_status` enum('active','suspended','withdrawn','graduated','expelled','on_break') DEFAULT NULL,
  `new_enrollment_status` enum('active','suspended','withdrawn','graduated','expelled','on_break') DEFAULT NULL,
  `old_academic_standing` enum('good','probation','warning','honors') DEFAULT NULL,
  `new_academic_standing` enum('good','probation','warning','honors') DEFAULT NULL,
  `old_semester` int(11) DEFAULT NULL,
  `new_semester` int(11) DEFAULT NULL,
  `old_cgpa` decimal(3,2) DEFAULT NULL,
  `new_cgpa` decimal(3,2) DEFAULT NULL,
  `old_completed_credit_hrs` int(11) DEFAULT NULL,
  `new_completed_credit_hrs` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submission_resource`
--

CREATE TABLE `submission_resource` (
  `submission_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_config_log`
--

CREATE TABLE `system_config_log` (
  `log_id` bigint(20) NOT NULL,
  `config_category` enum('user_management','course_management','enrollment','grading','financial','notification','security','system') NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `teacher_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `employee_number` varchar(50) NOT NULL,
  `designation` enum('lecturer','assistant_professor','associate_professor','professor','lab_assistant') NOT NULL,
  `hire_date` date NOT NULL,
  `employment_status` enum('active','on_leave','resigned','retired','terminated') NOT NULL DEFAULT 'active',
  `research_area` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`teacher_id`, `department_id`, `program_id`, `employee_number`, `designation`, `hire_date`, `employment_status`, `research_area`, `created_at`, `updated_at`) VALUES
(13, 2, NULL, 'CS-001-EMP-00001', 'assistant_professor', '2025-12-28', 'active', NULL, '2025-12-28 10:50:48', '2025-12-28 10:50:48'),
(18, 2, NULL, 'CS-001-EMP-00002', 'lecturer', '2025-12-31', 'active', NULL, '2025-12-31 11:22:03', '2025-12-31 11:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_availability`
--

CREATE TABLE `teacher_availability` (
  `availability_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_program`
--

CREATE TABLE `teacher_program` (
  `teacher_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `timetable_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `recurrence` enum('none','weekly') NOT NULL DEFAULT 'weekly',
  `status` enum('scheduled','cancelled','completed') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(201) GENERATED ALWAYS AS (concat(`first_name`,' ',`last_name`)) STORED,
  `email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Stores profile image filename (stored in /public/uploads/profiles/)',
  `campus_id` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `cnic` varchar(15) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_code` varchar(255) NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `contact_number`, `profile_image`, `campus_id`, `gender`, `cnic`, `date_of_birth`, `address`, `password_hash`, `user_code`, `status`, `is_deleted`, `created_at`, `updated_at`, `last_login_at`) VALUES
(2, 'Al', 'D', '', NULL, 'default_avatar.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$LmjCW7Tu6c5HGinLlg4LSOx40iKdr0OJkeH7AsSP2qvWhKQPhffpW', 'UI-ADM-2500001-6', 'active', 0, '2025-12-26 19:06:41', '2025-12-29 20:31:28', NULL),
(5, 'Ali', 'Danish', NULL, NULL, 'default_avatar.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$nSksDSmHBk9vwrnPhljvruN2LNN/6blA30AuTIT9gTW7PfF5MvTEe', 'UI07-2500002-216', 'inactive', 1, '2025-12-26 19:18:17', '2025-12-29 20:31:28', NULL),
(6, 'Ali', 'Danish', 'alidanish200430@gmail.com', '03218255486', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$OBW49sYOx0DT8bhUNGpkuecZOlQwAXETm1cFbT2tG/4YXt7I9GXzy', 'U202584900', 'active', 0, '2025-12-26 19:59:17', '2025-12-29 20:31:28', NULL),
(8, 'teacher', 'A', 'admin@gmail.com', '03218255486', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$WFecL4F2AfP80NpO.G5BY.pqxqlRWI4.5T2DaXc1ms2K6uzWWBgnW', 'U202533770', 'active', 0, '2025-12-26 21:13:36', '2025-12-29 20:31:28', NULL),
(12, 'Ali', 'Danish', 'admin1@gmail.com', '', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$d2eeuIWpjGT4Ty9h.CjGg.37UCIJkexKi1mXJ0zuG86ZH7fPbi2jy', 'UI07-2500004-214', 'active', 0, '2025-12-28 10:41:22', '2025-12-29 20:31:28', NULL),
(13, 'faraz', 'Abdul basit', 'Faraz@gmail.com', '03218255486', 'default_avatar.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$bcsghIBWE2x.EfdMEhvZS.lyCsb5vW1AqUXnfJsQaEKSC..4Xtyhi', 'U202554851', 'active', 0, '2025-12-28 10:50:48', '2025-12-29 20:31:28', NULL),
(15, 'syed Misbah', 'uddin', 'syedmisbahuddin@gmail.com', '0312000000', 'user_15_1767623927.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$uc.qr96G5nltduzFsICtRO5cd.3T8wJriUfISrNUF0PktbfMPVQ1G', 'UI01-2500001-4', 'active', 0, '2025-12-28 13:05:25', '2026-01-05 14:38:47', NULL),
(16, 'Admin', 'Admin', NULL, NULL, 'default_avatar.png', NULL, NULL, NULL, NULL, NULL, '$2y$10$RgB0Diie9lE0w2kYGUqD9ulKm1WyLl8BaAayyknPOavvoXEPPAg6K', 'UI07-2500001-1', 'active', 0, '2025-12-29 19:34:13', '2025-12-29 20:31:28', NULL),
(18, 'Faraz', 'Abdul Basit', 'faraz1@gmail.com', '01234567890', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$8UmlHwte2GW5qsCjIdaCy.D4K7LmLQN12Kk.jBdSOfjkqoYpXLOym', 'UI02-2500001-2', 'active', 0, '2025-12-31 11:22:03', '2025-12-31 11:22:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `log_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('login','logout','failed_login','create','update','delete','view','upload','download','export','password_change','password_reset','role_change','status_change') NOT NULL,
  `table_name` varchar(100) DEFAULT NULL COMMENT 'Table affected by action',
  `record_id` int(11) DEFAULT NULL COMMENT 'ID of affected record',
  `action_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `user_agent` text DEFAULT NULL,
  `request_url` varchar(500) DEFAULT NULL,
  `request_method` enum('GET','POST','PUT','DELETE','PATCH') DEFAULT NULL,
  `status` enum('success','failed','error') NOT NULL DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_code_sequence`
--

CREATE TABLE `user_code_sequence` (
  `role` varchar(50) NOT NULL,
  `year` char(2) NOT NULL,
  `last_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_code_sequence`
--

INSERT INTO `user_code_sequence` (`role`, `year`, `last_number`) VALUES
('admin', '25', 1),
('faculty', '25', 1),
('student', '25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_notification`
--

CREATE TABLE `user_notification` (
  `user_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notification`
--

INSERT INTO `user_notification` (`user_id`, `notification_id`, `is_read`, `read_at`) VALUES
(2, 1, 0, NULL),
(6, 1, 0, NULL),
(6, 2, 0, NULL),
(6, 3, 0, NULL),
(8, 1, 0, NULL),
(8, 2, 0, NULL),
(8, 3, 0, NULL),
(12, 1, 1, '2025-12-31 16:20:20'),
(13, 1, 0, NULL),
(15, 3, 1, '2026-01-05 19:38:15'),
(16, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(2, 3),
(5, 3),
(6, 1),
(8, 1),
(12, 3),
(13, 2),
(15, 1),
(16, 3),
(18, 2);

-- --------------------------------------------------------

--
-- Table structure for table `waiting_list`
--

CREATE TABLE `waiting_list` (
  `waiting_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `offering_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  `priority_number` int(11) NOT NULL,
  `status` enum('waiting','enrolled','cancelled','expired') NOT NULL DEFAULT 'waiting',
  `notified_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD PRIMARY KEY (`calendar_id`),
  ADD UNIQUE KEY `unique_academic_period` (`academic_year`,`semester`,`term`),
  ADD KEY `idx_academic_calendar_year` (`academic_year`),
  ADD KEY `idx_academic_calendar_status` (`status`);

--
-- Indexes for table `admission`
--
ALTER TABLE `admission`
  ADD PRIMARY KEY (`admission_id`),
  ADD KEY `idx_admission_user` (`user_id`),
  ADD KEY `idx_admission_program` (`program_id`),
  ADD KEY `idx_admission_status` (`status`);

--
-- Indexes for table `admission_document`
--
ALTER TABLE `admission_document`
  ADD PRIMARY KEY (`admission_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `admission_status_log`
--
ALTER TABLE `admission_status_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_admission_log_admission` (`admission_id`),
  ADD KEY `idx_admission_log_status` (`new_status`),
  ADD KEY `idx_admission_log_created` (`created_at`);

--
-- Indexes for table `assessment_weight`
--
ALTER TABLE `assessment_weight`
  ADD PRIMARY KEY (`offering_id`,`assessment_type`,`assessment_id`);

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_assignment_module` (`module_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `assignment_resource`
--
ALTER TABLE `assignment_resource`
  ADD PRIMARY KEY (`assignment_id`,`resource_id`),
  ADD UNIQUE KEY `uq_assignment_primary` (`assignment_id`,`is_primary`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `assignment_submission`
--
ALTER TABLE `assignment_submission`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `assignment_id` (`assignment_id`,`student_id`,`attempt_number`),
  ADD KEY `graded_by` (`graded_by`),
  ADD KEY `idx_submission_assignment` (`assignment_id`),
  ADD KEY `idx_submission_student` (`student_id`),
  ADD KEY `submission_resource_id` (`submission_resource_id`);

--
-- Indexes for table `attendance_change_log`
--
ALTER TABLE `attendance_change_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_attendance_log_record` (`record_id`),
  ADD KEY `idx_attendance_log_session` (`session_id`),
  ADD KEY `idx_attendance_log_student` (`student_id`),
  ADD KEY `idx_attendance_log_created` (`created_at`);

--
-- Indexes for table `attendance_record`
--
ALTER TABLE `attendance_record`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `uq_session_student` (`session_id`,`student_id`),
  ADD KEY `idx_attendance_record_session` (`session_id`),
  ADD KEY `idx_attendance_record_student` (`student_id`);

--
-- Indexes for table `attendance_session`
--
ALTER TABLE `attendance_session`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_attendance_session_lesson` (`lesson_id`),
  ADD KEY `idx_attendance_session_offering` (`offering_id`);

--
-- Indexes for table `bulk_operation_log`
--
ALTER TABLE `bulk_operation_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_bulk_log_type` (`operation_type`),
  ADD KEY `idx_bulk_log_initiated_by` (`initiated_by`),
  ADD KEY `idx_bulk_log_status` (`status`),
  ADD KEY `idx_bulk_log_created` (`created_at`);

--
-- Indexes for table `campus`
--
ALTER TABLE `campus`
  ADD PRIMARY KEY (`campus_id`),
  ADD UNIQUE KEY `campus_code` (`campus_code`),
  ADD KEY `idx_campus_status` (`status`),
  ADD KEY `idx_campus_deleted` (`is_deleted`);

--
-- Indexes for table `certificate`
--
ALTER TABLE `certificate`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `certificate_resource_id` (`certificate_resource_id`),
  ADD KEY `issued_by` (`issued_by`);

--
-- Indexes for table `content_change_log`
--
ALTER TABLE `content_change_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_content_log_type` (`content_type`,`content_id`),
  ADD KEY `idx_content_log_action` (`action`),
  ADD KEY `idx_content_log_changed_by` (`changed_by`),
  ADD KEY `idx_content_log_created` (`created_at`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_course_department` (`department_id`),
  ADD KEY `idx_course_deleted` (`is_deleted`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `course_evaluation`
--
ALTER TABLE `course_evaluation`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD UNIQUE KEY `unique_evaluation` (`offering_id`,`student_id`),
  ADD KEY `idx_course_evaluation_offering` (`offering_id`),
  ADD KEY `idx_course_evaluation_student` (`student_id`);

--
-- Indexes for table `course_offering`
--
ALTER TABLE `course_offering`
  ADD PRIMARY KEY (`offering_id`),
  ADD UNIQUE KEY `course_id` (`course_id`,`academic_year`,`semester`,`term`),
  ADD KEY `idx_course_offering_course` (`course_id`),
  ADD KEY `idx_course_offering_year_sem` (`academic_year`,`semester`);

--
-- Indexes for table `course_prerequisite`
--
ALTER TABLE `course_prerequisite`
  ADD PRIMARY KEY (`course_id`,`prerequisite_course_id`),
  ADD KEY `prerequisite_course_id` (`prerequisite_course_id`);

--
-- Indexes for table `course_section`
--
ALTER TABLE `course_section`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `unique_offering_section` (`offering_id`,`section_name`),
  ADD KEY `idx_course_section_offering` (`offering_id`);

--
-- Indexes for table `course_teacher`
--
ALTER TABLE `course_teacher`
  ADD PRIMARY KEY (`course_teacher_id`),
  ADD UNIQUE KEY `offering_id` (`offering_id`,`teacher_id`,`role`),
  ADD KEY `idx_course_teacher_offering` (`offering_id`),
  ADD KEY `idx_course_teacher_teacher` (`teacher_id`);

--
-- Indexes for table `database_change_log`
--
ALTER TABLE `database_change_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_db_change_log_type` (`change_type`),
  ADD KEY `idx_db_change_log_object` (`object_type`,`object_name`),
  ADD KEY `idx_db_change_log_executed_by` (`executed_by`),
  ADD KEY `idx_db_change_log_created` (`created_at`);

--
-- Indexes for table `data_export_log`
--
ALTER TABLE `data_export_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_export_log_user` (`user_id`),
  ADD KEY `idx_export_log_type` (`export_type`),
  ADD KEY `idx_export_log_status` (`status`),
  ADD KEY `idx_export_log_created` (`created_at`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `idx_department_deleted` (`is_deleted`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `campus_id` (`campus_id`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`offering_id`),
  ADD KEY `idx_enrollment_student` (`student_id`),
  ADD KEY `idx_enrollment_offering` (`offering_id`),
  ADD KEY `idx_enrollment_status` (`status`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `enrollment_log`
--
ALTER TABLE `enrollment_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_enrollment_log_enrollment` (`enrollment_id`),
  ADD KEY `idx_enrollment_log_action` (`action`),
  ADD KEY `idx_enrollment_log_created` (`created_at`);

--
-- Indexes for table `enrollment_withdrawal`
--
ALTER TABLE `enrollment_withdrawal`
  ADD PRIMARY KEY (`withdrawal_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_withdrawal_enrollment` (`enrollment_id`);

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `idx_exam_offering` (`offering_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `exam_attempt`
--
ALTER TABLE `exam_attempt`
  ADD PRIMARY KEY (`attempt_id`),
  ADD UNIQUE KEY `uq_exam_student_attempt` (`exam_id`,`student_id`,`attempt_number`),
  ADD KEY `graded_by` (`graded_by`),
  ADD KEY `idx_exam_attempt_exam` (`exam_id`),
  ADD KEY `idx_exam_attempt_student` (`student_id`);

--
-- Indexes for table `exam_integrity_log`
--
ALTER TABLE `exam_integrity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_exam_integrity_attempt` (`attempt_id`),
  ADD KEY `idx_exam_integrity_exam` (`exam_id`),
  ADD KEY `idx_exam_integrity_student` (`student_id`),
  ADD KEY `idx_exam_integrity_type` (`event_type`),
  ADD KEY `idx_exam_integrity_severity` (`severity`),
  ADD KEY `idx_exam_integrity_created` (`created_at`);

--
-- Indexes for table `exam_resource`
--
ALTER TABLE `exam_resource`
  ADD PRIMARY KEY (`exam_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `exam_section`
--
ALTER TABLE `exam_section`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `exam_id` (`exam_id`,`sequence_number`);

--
-- Indexes for table `exam_section_mark`
--
ALTER TABLE `exam_section_mark`
  ADD PRIMARY KEY (`attempt_id`,`section_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `faculty_code` (`faculty_code`),
  ADD KEY `idx_faculty_deleted` (`is_deleted`);

--
-- Indexes for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD PRIMARY KEY (`program_id`,`fee_type`);

--
-- Indexes for table `file_access_log`
--
ALTER TABLE `file_access_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_file_log_resource` (`resource_id`),
  ADD KEY `idx_file_log_user` (`user_id`),
  ADD KEY `idx_file_log_type` (`access_type`),
  ADD KEY `idx_file_log_created` (`created_at`);

--
-- Indexes for table `forum`
--
ALTER TABLE `forum`
  ADD PRIMARY KEY (`forum_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_forum_course` (`course_id`);

--
-- Indexes for table `forum_post`
--
ALTER TABLE `forum_post`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `parent_post_id` (`parent_post_id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `idx_forum_post_thread` (`thread_id`);

--
-- Indexes for table `forum_post_resource`
--
ALTER TABLE `forum_post_resource`
  ADD PRIMARY KEY (`post_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `forum_thread`
--
ALTER TABLE `forum_thread`
  ADD PRIMARY KEY (`thread_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_forum_thread_forum` (`forum_id`);

--
-- Indexes for table `grade_appeal`
--
ALTER TABLE `grade_appeal`
  ADD PRIMARY KEY (`appeal_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_grade_appeal_enrollment` (`enrollment_id`),
  ADD KEY `idx_grade_appeal_student` (`student_id`),
  ADD KEY `idx_grade_appeal_status` (`status`);

--
-- Indexes for table `grade_change_log`
--
ALTER TABLE `grade_change_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_grade_log_student` (`student_id`),
  ADD KEY `idx_grade_log_type` (`record_type`,`record_id`),
  ADD KEY `idx_grade_log_created` (`created_at`),
  ADD KEY `idx_grade_log_approval` (`approval_status`);

--
-- Indexes for table `grade_component`
--
ALTER TABLE `grade_component`
  ADD PRIMARY KEY (`component_id`),
  ADD KEY `idx_grade_component_offering` (`offering_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invoice_student` (`student_id`),
  ADD KEY `idx_invoice_status` (`status`);

--
-- Indexes for table `lesson`
--
ALTER TABLE `lesson`
  ADD PRIMARY KEY (`lesson_id`),
  ADD UNIQUE KEY `module_id` (`module_id`,`sequence_number`),
  ADD KEY `idx_lesson_module` (`module_id`);

--
-- Indexes for table `lesson_resource`
--
ALTER TABLE `lesson_resource`
  ADD PRIMARY KEY (`lesson_id`,`resource_id`),
  ADD UNIQUE KEY `uq_lesson_primary` (`lesson_id`,`is_primary`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `log_retention_policy`
--
ALTER TABLE `log_retention_policy`
  ADD PRIMARY KEY (`policy_id`),
  ADD UNIQUE KEY `log_table_name` (`log_table_name`);

--
-- Indexes for table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`module_id`),
  ADD UNIQUE KEY `offering_id` (`offering_id`,`module_code`),
  ADD UNIQUE KEY `offering_id_2` (`offering_id`,`sequence_number`),
  ADD KEY `idx_module_offering` (`offering_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notification_delivery_log`
--
ALTER TABLE `notification_delivery_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_notif_delivery_log_notification` (`notification_id`),
  ADD KEY `idx_notif_delivery_log_user` (`user_id`),
  ADD KEY `idx_notif_delivery_log_status` (`status`),
  ADD KEY `idx_notif_delivery_log_method` (`delivery_method`),
  ADD KEY `idx_notif_delivery_log_created` (`created_at`);

--
-- Indexes for table `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD KEY `notification_id` (`notification_id`),
  ADD KEY `idx_notification_queue_status` (`status`),
  ADD KEY `idx_notification_queue_user` (`user_id`),
  ADD KEY `idx_notification_queue_scheduled` (`scheduled_for`);

--
-- Indexes for table `notification_resource`
--
ALTER TABLE `notification_resource`
  ADD PRIMARY KEY (`notification_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_payment_invoice` (`invoice_id`);

--
-- Indexes for table `payment_transaction_log`
--
ALTER TABLE `payment_transaction_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_payment_log_payment` (`payment_id`),
  ADD KEY `idx_payment_log_invoice` (`invoice_id`),
  ADD KEY `idx_payment_log_student` (`student_id`),
  ADD KEY `idx_payment_log_type` (`transaction_type`),
  ADD KEY `idx_payment_log_status` (`status`),
  ADD KEY `idx_payment_log_created` (`created_at`);

--
-- Indexes for table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_program_deleted` (`is_deleted`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `program_course`
--
ALTER TABLE `program_course`
  ADD PRIMARY KEY (`program_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `resource`
--
ALTER TABLE `resource`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `roles_permissions`
--
ALTER TABLE `roles_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `role_change_requests`
--
ALTER TABLE `role_change_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `new_role_id` (`new_role_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `role_permission_log`
--
ALTER TABLE `role_permission_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_role_perm_log_user` (`user_id`),
  ADD KEY `idx_role_perm_log_type` (`change_type`),
  ADD KEY `idx_role_perm_log_created` (`created_at`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `campus_id` (`campus_id`);

--
-- Indexes for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD PRIMARY KEY (`room_id`,`day_of_week`,`start_time`);

--
-- Indexes for table `security_incident_log`
--
ALTER TABLE `security_incident_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_security_log_type` (`incident_type`),
  ADD KEY `idx_security_log_severity` (`severity`),
  ADD KEY `idx_security_log_ip` (`ip_address`),
  ADD KEY `idx_security_log_resolved` (`resolved`),
  ADD KEY `idx_security_log_created` (`created_at`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `idx_student_program` (`program_id`),
  ADD KEY `idx_student_enrollment_status` (`enrollment_status`);

--
-- Indexes for table `student_number_sequence`
--
ALTER TABLE `student_number_sequence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_year_program` (`year`,`program_code`);

--
-- Indexes for table `student_program_change_requests`
--
ALTER TABLE `student_program_change_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `student_status_log`
--
ALTER TABLE `student_status_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_student_log_student` (`student_id`),
  ADD KEY `idx_student_log_type` (`change_type`),
  ADD KEY `idx_student_log_created` (`created_at`);

--
-- Indexes for table `submission_resource`
--
ALTER TABLE `submission_resource`
  ADD PRIMARY KEY (`submission_id`,`resource_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `system_config_log`
--
ALTER TABLE `system_config_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_config_log_category` (`config_category`),
  ADD KEY `idx_config_log_key` (`config_key`),
  ADD KEY `idx_config_log_changed_by` (`changed_by`),
  ADD KEY `idx_config_log_created` (`created_at`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `idx_teacher_department` (`department_id`),
  ADD KEY `idx_teacher_program` (`program_id`);

--
-- Indexes for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD UNIQUE KEY `unique_teacher_schedule` (`teacher_id`,`day_of_week`,`start_time`),
  ADD KEY `idx_teacher_availability_teacher` (`teacher_id`),
  ADD KEY `idx_teacher_availability_day` (`day_of_week`);

--
-- Indexes for table `teacher_program`
--
ALTER TABLE `teacher_program`
  ADD PRIMARY KEY (`teacher_id`,`program_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`timetable_id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_code` (`user_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `cnic` (`cnic`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_user_code` (`user_code`),
  ADD KEY `idx_users_deleted` (`is_deleted`),
  ADD KEY `idx_users_cnic` (`cnic`),
  ADD KEY `campus_id` (`campus_id`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_activity_user` (`user_id`),
  ADD KEY `idx_user_activity_type` (`activity_type`),
  ADD KEY `idx_user_activity_table` (`table_name`),
  ADD KEY `idx_user_activity_created` (`created_at`),
  ADD KEY `idx_user_activity_status` (`status`);

--
-- Indexes for table `user_code_sequence`
--
ALTER TABLE `user_code_sequence`
  ADD PRIMARY KEY (`role`,`year`);

--
-- Indexes for table `user_notification`
--
ALTER TABLE `user_notification`
  ADD PRIMARY KEY (`user_id`,`notification_id`),
  ADD KEY `notification_id` (`notification_id`),
  ADD KEY `idx_user_notification_user` (`user_id`),
  ADD KEY `idx_user_notification_read` (`is_read`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `idx_user_roles_user` (`user_id`),
  ADD KEY `idx_user_roles_role` (`role_id`);

--
-- Indexes for table `waiting_list`
--
ALTER TABLE `waiting_list`
  ADD PRIMARY KEY (`waiting_id`),
  ADD UNIQUE KEY `unique_student_offering` (`student_id`,`offering_id`,`section_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `idx_waiting_list_student` (`student_id`),
  ADD KEY `idx_waiting_list_offering` (`offering_id`),
  ADD KEY `idx_waiting_list_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  MODIFY `calendar_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission`
--
ALTER TABLE `admission`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_status_log`
--
ALTER TABLE `admission_status_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assignment_submission`
--
ALTER TABLE `assignment_submission`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_change_log`
--
ALTER TABLE `attendance_change_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_record`
--
ALTER TABLE `attendance_record`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance_session`
--
ALTER TABLE `attendance_session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bulk_operation_log`
--
ALTER TABLE `bulk_operation_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campus`
--
ALTER TABLE `campus`
  MODIFY `campus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `certificate`
--
ALTER TABLE `certificate`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_change_log`
--
ALTER TABLE `content_change_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `course_evaluation`
--
ALTER TABLE `course_evaluation`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_offering`
--
ALTER TABLE `course_offering`
  MODIFY `offering_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `course_section`
--
ALTER TABLE `course_section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_teacher`
--
ALTER TABLE `course_teacher`
  MODIFY `course_teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `database_change_log`
--
ALTER TABLE `database_change_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_export_log`
--
ALTER TABLE `data_export_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `enrollment_log`
--
ALTER TABLE `enrollment_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment_withdrawal`
--
ALTER TABLE `enrollment_withdrawal`
  MODIFY `withdrawal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_attempt`
--
ALTER TABLE `exam_attempt`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_integrity_log`
--
ALTER TABLE `exam_integrity_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_section`
--
ALTER TABLE `exam_section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `file_access_log`
--
ALTER TABLE `file_access_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum`
--
ALTER TABLE `forum`
  MODIFY `forum_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_post`
--
ALTER TABLE `forum_post`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_thread`
--
ALTER TABLE `forum_thread`
  MODIFY `thread_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_appeal`
--
ALTER TABLE `grade_appeal`
  MODIFY `appeal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_change_log`
--
ALTER TABLE `grade_change_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_component`
--
ALTER TABLE `grade_component`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson`
--
ALTER TABLE `lesson`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `log_retention_policy`
--
ALTER TABLE `log_retention_policy`
  MODIFY `policy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `module`
--
ALTER TABLE `module`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notification_delivery_log`
--
ALTER TABLE `notification_delivery_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_queue`
--
ALTER TABLE `notification_queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transaction_log`
--
ALTER TABLE `payment_transaction_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permission`
--
ALTER TABLE `permission`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `program`
--
ALTER TABLE `program`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `resource`
--
ALTER TABLE `resource`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `role_change_requests`
--
ALTER TABLE `role_change_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permission_log`
--
ALTER TABLE `role_permission_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_incident_log`
--
ALTER TABLE `security_incident_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_number_sequence`
--
ALTER TABLE `student_number_sequence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_program_change_requests`
--
ALTER TABLE `student_program_change_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_status_log`
--
ALTER TABLE `student_status_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_config_log`
--
ALTER TABLE `system_config_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `waiting_list`
--
ALTER TABLE `waiting_list`
  MODIFY `waiting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission`
--
ALTER TABLE `admission`
  ADD CONSTRAINT `admission_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admission_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `program` (`program_id`) ON DELETE CASCADE;

--
-- Constraints for table `admission_document`
--
ALTER TABLE `admission_document`
  ADD CONSTRAINT `admission_document_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admission_document_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `admission_status_log`
--
ALTER TABLE `admission_status_log`
  ADD CONSTRAINT `admission_status_log_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admission_status_log_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_weight`
--
ALTER TABLE `assessment_weight`
  ADD CONSTRAINT `assessment_weight_ibfk_1` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `assignment_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`module_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assignment_resource`
--
ALTER TABLE `assignment_resource`
  ADD CONSTRAINT `assignment_resource_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignment` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_resource_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submission`
--
ALTER TABLE `assignment_submission`
  ADD CONSTRAINT `assignment_submission_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignment` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submission_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submission_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assignment_submission_ibfk_4` FOREIGN KEY (`submission_resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_change_log`
--
ALTER TABLE `attendance_change_log`
  ADD CONSTRAINT `attendance_change_log_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `attendance_record` (`record_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_change_log_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `attendance_session` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_change_log_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_change_log_ibfk_4` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_record`
--
ALTER TABLE `attendance_record`
  ADD CONSTRAINT `attendance_record_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `attendance_session` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_record_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_session`
--
ALTER TABLE `attendance_session`
  ADD CONSTRAINT `attendance_session_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lesson` (`lesson_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_session_ibfk_2` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE;

--
-- Constraints for table `bulk_operation_log`
--
ALTER TABLE `bulk_operation_log`
  ADD CONSTRAINT `bulk_operation_log_ibfk_1` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `certificate`
--
ALTER TABLE `certificate`
  ADD CONSTRAINT `certificate_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificate_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `program` (`program_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `certificate_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `certificate_ibfk_4` FOREIGN KEY (`certificate_resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `certificate_ibfk_5` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `content_change_log`
--
ALTER TABLE `content_change_log`
  ADD CONSTRAINT `content_change_log_ibfk_1` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `course_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_evaluation`
--
ALTER TABLE `course_evaluation`
  ADD CONSTRAINT `course_evaluation_ibfk_1` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_evaluation_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `course_offering`
--
ALTER TABLE `course_offering`
  ADD CONSTRAINT `course_offering_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON UPDATE CASCADE;

--
-- Constraints for table `course_prerequisite`
--
ALTER TABLE `course_prerequisite`
  ADD CONSTRAINT `course_prerequisite_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_prerequisite_ibfk_2` FOREIGN KEY (`prerequisite_course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_section`
--
ALTER TABLE `course_section`
  ADD CONSTRAINT `course_section_ibfk_1` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_teacher`
--
ALTER TABLE `course_teacher`
  ADD CONSTRAINT `course_teacher_ibfk_1` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_teacher_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`) ON UPDATE CASCADE;

--
-- Constraints for table `database_change_log`
--
ALTER TABLE `database_change_log`
  ADD CONSTRAINT `database_change_log_ibfk_1` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `data_export_log`
--
ALTER TABLE `data_export_log`
  ADD CONSTRAINT `data_export_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `department`
--
ALTER TABLE `department`
  ADD CONSTRAINT `department_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `department_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `department_ibfk_3` FOREIGN KEY (`campus_id`) REFERENCES `campus` (`campus_id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `enrollment_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `enrollment_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `course_section` (`section_id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollment_log`
--
ALTER TABLE `enrollment_log`
  ADD CONSTRAINT `enrollment_log_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollment_withdrawal`
--
ALTER TABLE `enrollment_withdrawal`
  ADD CONSTRAINT `enrollment_withdrawal_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_withdrawal_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam`
--
ALTER TABLE `exam`
  ADD CONSTRAINT `exam_ibfk_1` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_attempt`
--
ALTER TABLE `exam_attempt`
  ADD CONSTRAINT `exam_attempt_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_attempt_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_attempt_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_integrity_log`
--
ALTER TABLE `exam_integrity_log`
  ADD CONSTRAINT `exam_integrity_log_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempt` (`attempt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_integrity_log_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_integrity_log_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_resource`
--
ALTER TABLE `exam_resource`
  ADD CONSTRAINT `exam_resource_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_resource_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_section`
--
ALTER TABLE `exam_section`
  ADD CONSTRAINT `exam_section_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_section_mark`
--
ALTER TABLE `exam_section_mark`
  ADD CONSTRAINT `exam_section_mark_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempt` (`attempt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_section_mark_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `exam_section` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD CONSTRAINT `fee_structure_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `program` (`program_id`) ON DELETE CASCADE;

--
-- Constraints for table `file_access_log`
--
ALTER TABLE `file_access_log`
  ADD CONSTRAINT `file_access_log_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_access_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `forum`
--
ALTER TABLE `forum`
  ADD CONSTRAINT `forum_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `module` (`module_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `forum_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `forum_post`
--
ALTER TABLE `forum_post`
  ADD CONSTRAINT `forum_post_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `forum_thread` (`thread_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_post_ibfk_2` FOREIGN KEY (`parent_post_id`) REFERENCES `forum_post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_post_ibfk_3` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `forum_post_resource`
--
ALTER TABLE `forum_post_resource`
  ADD CONSTRAINT `forum_post_resource_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `forum_post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_post_resource_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_thread`
--
ALTER TABLE `forum_thread`
  ADD CONSTRAINT `forum_thread_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `forum` (`forum_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_thread_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grade_appeal`
--
ALTER TABLE `grade_appeal`
  ADD CONSTRAINT `grade_appeal_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_appeal_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_appeal_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grade_change_log`
--
ALTER TABLE `grade_change_log`
  ADD CONSTRAINT `grade_change_log_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_change_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `grade_change_log_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grade_component`
--
ALTER TABLE `grade_component`
  ADD CONSTRAINT `grade_component_ibfk_1` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_ibfk_2` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`enrollment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lesson`
--
ALTER TABLE `lesson`
  ADD CONSTRAINT `lesson_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`module_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_resource`
--
ALTER TABLE `lesson_resource`
  ADD CONSTRAINT `lesson_resource_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lesson` (`lesson_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_resource_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `module`
--
ALTER TABLE `module`
  ADD CONSTRAINT `module_ibfk_1` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_delivery_log`
--
ALTER TABLE `notification_delivery_log`
  ADD CONSTRAINT `notification_delivery_log_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notification` (`notification_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notification_delivery_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD CONSTRAINT `notification_queue_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notification` (`notification_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_queue_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_resource`
--
ALTER TABLE `notification_resource`
  ADD CONSTRAINT `notification_resource_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notification` (`notification_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_resource_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`invoice_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_transaction_log`
--
ALTER TABLE `payment_transaction_log`
  ADD CONSTRAINT `payment_transaction_log_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_transaction_log_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`invoice_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_transaction_log_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_transaction_log_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `program`
--
ALTER TABLE `program`
  ADD CONSTRAINT `program_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `program_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `program_course`
--
ALTER TABLE `program_course`
  ADD CONSTRAINT `program_course_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `program` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `program_course_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `resource`
--
ALTER TABLE `resource`
  ADD CONSTRAINT `resource_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `roles_permissions`
--
ALTER TABLE `roles_permissions`
  ADD CONSTRAINT `roles_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_change_requests`
--
ALTER TABLE `role_change_requests`
  ADD CONSTRAINT `role_change_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `role_change_requests_ibfk_2` FOREIGN KEY (`new_role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `role_change_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `role_change_requests_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_permission_log`
--
ALTER TABLE `role_permission_log`
  ADD CONSTRAINT `role_permission_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `role_permission_log_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `role_permission_log_ibfk_3` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `role_permission_log_ibfk_4` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `room_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campus` (`campus_id`) ON DELETE SET NULL;

--
-- Constraints for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD CONSTRAINT `room_availability_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `security_incident_log`
--
ALTER TABLE `security_incident_log`
  ADD CONSTRAINT `security_incident_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `security_incident_log_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `program` (`program_id`) ON UPDATE CASCADE;

--
-- Constraints for table `student_program_change_requests`
--
ALTER TABLE `student_program_change_requests`
  ADD CONSTRAINT `student_program_change_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `student_program_change_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `student_program_change_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `student_status_log`
--
ALTER TABLE `student_status_log`
  ADD CONSTRAINT `student_status_log_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_status_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `submission_resource`
--
ALTER TABLE `submission_resource`
  ADD CONSTRAINT `submission_resource_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `assignment_submission` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submission_resource_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_config_log`
--
ALTER TABLE `system_config_log`
  ADD CONSTRAINT `system_config_log_ibfk_1` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `teacher_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `teacher_ibfk_3` FOREIGN KEY (`program_id`) REFERENCES `program` (`program_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD CONSTRAINT `teacher_availability_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `teacher_program`
--
ALTER TABLE `teacher_program`
  ADD CONSTRAINT `teacher_program_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_program_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `program` (`program_id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lesson` (`lesson_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campus` (`campus_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_notification`
--
ALTER TABLE `user_notification`
  ADD CONSTRAINT `user_notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_notification_ibfk_2` FOREIGN KEY (`notification_id`) REFERENCES `notification` (`notification_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `waiting_list`
--
ALTER TABLE `waiting_list`
  ADD CONSTRAINT `waiting_list_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `waiting_list_ibfk_2` FOREIGN KEY (`offering_id`) REFERENCES `course_offering` (`offering_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `waiting_list_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `course_section` (`section_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
