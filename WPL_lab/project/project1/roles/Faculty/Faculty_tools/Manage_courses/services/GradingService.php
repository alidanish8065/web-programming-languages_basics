<?php
/**
 * GradingService - Handle assignment submission grading
 */
class GradingService {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /**
     * Grade a student submission
     */
    public function gradeSubmission(int $submissionId, int $marks, string $feedback, int $graderId): bool {
        $stmt = $this->db->prepare(
            "UPDATE assignment_submission 
             SET marks_obtained = ?, feedback = ?, status = 'graded', graded_by = ?, graded_at = NOW() 
             WHERE submission_id = ?"
        );
        $stmt->bind_param("isii", $marks, $feedback, $graderId, $submissionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get pending submissions for an assignment
     */
    public function getPendingSubmissions(int $assignmentId, int $limit = 3): array {
        $stmt = $this->db->prepare(
            "SELECT asub.submission_id, asub.submitted_at, 
                    CONCAT(u.first_name, ' ', u.last_name) as student_name
             FROM assignment_submission asub
             JOIN users u ON asub.student_id = u.id
             WHERE asub.assignment_id = ? AND asub.status = 'submitted'
             LIMIT ?"
        );
        $stmt->bind_param("ii", $assignmentId, $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
}
