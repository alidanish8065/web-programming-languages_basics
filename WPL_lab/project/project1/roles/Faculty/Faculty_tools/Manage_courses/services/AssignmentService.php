<?php
/**
 * AssignmentService - Handle assignment CRUD with resources
 */

class AssignmentService {
    private mysqli $db;
    private string $uploadDir;

    public function __construct(mysqli $db) {
        $this->db = $db;
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/project1/public/uploads/resources/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Create a new assignment with optional file
     */
    public function create(array $data, array $files, int $teacherId): bool {
        $moduleId = (int)$data['module_id'];
        $title = trim($data['assignment_title']);
        $desc = trim($data['assignment_desc'] ?? '');
        $maxMarks = (int)$data['max_marks'];
        $weightage = (float)$data['weightage'];
        $dueDate = $data['due_date'];
        $allowLate = isset($data['allow_late']) ? 1 : 0;
        $penalty = $allowLate && !empty($data['late_penalty']) ? (float)$data['late_penalty'] : null;
        
        $stmt = $this->db->prepare(
            "INSERT INTO assignment 
             (module_id, assignment_title, description, max_marks, weightage, due_date, allow_late_submission, late_penalty_percent, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')"
        );
        $stmt->bind_param("issidsid", $moduleId, $title, $desc, $maxMarks, $weightage, $dueDate, $allowLate, $penalty);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        
        $assignmentId = $this->db->insert_id;
        $stmt->close();
        
        // Handle file upload
        if (isset($files['assignment_file']) && $files['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $this->addFileResource($assignmentId, $files['assignment_file'], $data, $teacherId);
        }
        
        return true;
    }

    /**
     * Add file resource to an assignment
     */
    private function addFileResource(int $assignmentId, array $file, array $data, int $teacherId): void {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'assign_' . time() . '_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($file['tmp_name'], $this->uploadDir . $filename)) {
            $path = 'public/uploads/resources/' . $filename;
            $resName = !empty($data['assignment_resource_name']) ? trim($data['assignment_resource_name']) : $file['name'];
            $resType = $data['assignment_resource_type'] ?? 'pdf';
            
            $stmt = $this->db->prepare(
                "INSERT INTO resource (resource_type, resource_url, resource_name, uploaded_by) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("sssi", $resType, $path, $resName, $teacherId);
            $stmt->execute();
            $resourceId = $this->db->insert_id;
            $stmt->close();
            
            $stmt = $this->db->prepare(
                "INSERT INTO assignment_resource (assignment_id, resource_id, is_primary) VALUES (?, ?, 1)"
            );
            $stmt->bind_param("ii", $assignmentId, $resourceId);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Get assignments for a module with pending count
     */
    public function getByModuleId(int $moduleId): array {
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
     * Delete an assignment
     */
    public function delete(int $assignmentId): bool {
        $stmt = $this->db->prepare("DELETE FROM assignment WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignmentId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
