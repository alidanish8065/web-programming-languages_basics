<?php
/**
 * CourseDataService - Handle course data fetching and access verification
 */
class CourseDataService {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /**
     * Get all courses for a teacher
     */
    public function getTeacherCourses(int $teacherId): array {
        $sql = "
            SELECT co.offering_id, c.course_code, c.course_name, c.credit_hrs,
                   co.academic_year, co.semester, co.term, d.department_name,
                   COUNT(DISTINCT e.student_id) as student_count
            FROM course_teacher ct
            JOIN course_offering co ON ct.offering_id = co.offering_id
            JOIN course c ON co.course_id = c.course_id
            JOIN department d ON c.department_id = d.department_id
            LEFT JOIN enrollment e ON co.offering_id = e.offering_id AND e.status = 'enrolled'
            WHERE ct.teacher_id = ?
            GROUP BY co.offering_id
            ORDER BY co.academic_year DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $teacherId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Verify teacher has access to a course offering
     */
    public function verifyCourseAccess(int $offeringId, int $teacherId): ?array {
        $stmt = $this->db->prepare("
            SELECT c.course_id, c.course_code, c.course_name, d.department_name
            FROM course_teacher ct
            JOIN course_offering co ON ct.offering_id = co.offering_id
            JOIN course c ON co.course_id = c.course_id
            JOIN department d ON c.department_id = d.department_id
            WHERE co.offering_id = ? AND ct.teacher_id = ?
        ");
        $stmt->bind_param("ii", $offeringId, $teacherId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Get modules with their lessons and assignments for a course
     */
    public function getModulesWithContent(int $offeringId): array {
        $modules = [];
        
        $stmt = $this->db->prepare(
            "SELECT * FROM module WHERE offering_id = ? AND status = 'active' ORDER BY sequence_number"
        );
        $stmt->bind_param("i", $offeringId);
        $stmt->execute();
        $modulesRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($modulesRaw as $mod) {
            $mid = $mod['module_id'];
            $modules[$mid] = $mod;
            $modules[$mid]['lessons'] = $this->getLessonsWithResources($mid);
            $modules[$mid]['assignments'] = $this->getAssignmentsWithPending($mid);
        }
        
        return $modules;
    }

    /**
     * Get lessons with their resources for a module
     */
    private function getLessonsWithResources(int $moduleId): array {
        $stmt = $this->db->prepare("
            SELECT l.*, 
                   GROUP_CONCAT(CONCAT(r.resource_type, ':', r.resource_name, ':', r.resource_url) SEPARATOR '|||') as resources
            FROM lesson l
            LEFT JOIN lesson_resource lr ON l.lesson_id = lr.lesson_id
            LEFT JOIN resource r ON lr.resource_id = r.resource_id
            WHERE l.module_id = ?
            GROUP BY l.lesson_id
            ORDER BY l.sequence_number
        ");
        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Get assignments with pending submission count
     */
    private function getAssignmentsWithPending(int $moduleId): array {
        $stmt = $this->db->prepare("
            SELECT a.*,
                   (SELECT COUNT(*) FROM assignment_submission WHERE assignment_id = a.assignment_id AND status = 'submitted') as pending
            FROM assignment a
            WHERE a.module_id = ?
            ORDER BY a.due_date
        ");
        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Calculate next module sequence number
     */
    public function getNextModuleSequence(array $modules): int {
        if (empty($modules)) {
            return 1;
        }
        return max(array_column($modules, 'sequence_number')) + 1;
    }
}
