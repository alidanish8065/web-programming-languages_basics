<?php
/**
 * LessonService - Handle lesson CRUD with resources
 */

class LessonService {
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
     * Create a new lesson with optional resources
     */
    public function create(array $data, array $files, int $teacherId): bool {
        $moduleId = (int)$data['module_id'];
        $title = trim($data['lesson_title']);
        $type = $data['lesson_type'];
        $desc = trim($data['lesson_desc'] ?? '');
        $seq = (int)$data['sequence'];
        $scheduledStart = !empty($data['scheduled_start']) ? $data['scheduled_start'] : null;
        
        $stmt = $this->db->prepare(
            "INSERT INTO lesson 
             (module_id, lesson_title, lesson_type, description, sequence_number, scheduled_start, status) 
             VALUES (?, ?, ?, ?, ?, ?, 'published')"
        );
        $stmt->bind_param("isssis", $moduleId, $title, $type, $desc, $seq, $scheduledStart);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        
        $lessonId = $this->db->insert_id;
        $stmt->close();
        
        // Handle file upload
        if (isset($files['resource_file']) && $files['resource_file']['error'] === UPLOAD_ERR_OK) {
            $this->addFileResource($lessonId, $files['resource_file'], $data, $teacherId);
        }
        
        // Handle URL resource
        if (!empty($data['resource_url'])) {
            $this->addUrlResource($lessonId, $data, $teacherId);
        }
        
        return true;
    }

    /**
     * Add file resource to a lesson
     */
    private function addFileResource(int $lessonId, array $file, array $data, int $teacherId): void {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'res_' . time() . '_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($file['tmp_name'], $this->uploadDir . $filename)) {
            $path = 'public/uploads/resources/' . $filename;
            $resName = !empty($data['resource_name']) ? trim($data['resource_name']) : $file['name'];
            $resType = $data['resource_type'];
            
            $resourceId = $this->insertResource($resType, $path, $resName, $teacherId);
            $this->linkResourceToLesson($lessonId, $resourceId);
        }
    }

    /**
     * Add URL resource to a lesson
     */
    private function addUrlResource(int $lessonId, array $data, int $teacherId): void {
        $url = trim($data['resource_url']);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $resName = !empty($data['url_name']) ? trim($data['url_name']) : $url;
            
            $resourceId = $this->insertResource('link', $url, $resName, $teacherId);
            $this->linkResourceToLesson($lessonId, $resourceId);
        }
    }

    /**
     * Insert a resource record
     */
    private function insertResource(string $type, string $url, string $name, int $uploadedBy): int {
        $stmt = $this->db->prepare(
            "INSERT INTO resource (resource_type, resource_url, resource_name, uploaded_by) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssi", $type, $url, $name, $uploadedBy);
        $stmt->execute();
        $id = $this->db->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Link resource to lesson
     */
    private function linkResourceToLesson(int $lessonId, int $resourceId): void {
        $stmt = $this->db->prepare(
            "INSERT INTO lesson_resource (lesson_id, resource_id, is_primary) VALUES (?, ?, 1)"
        );
        $stmt->bind_param("ii", $lessonId, $resourceId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Get lessons for a module
     */
    public function getByModuleId(int $moduleId): array {
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
     * Delete a lesson
     */
    public function delete(int $lessonId): bool {
        $stmt = $this->db->prepare("DELETE FROM lesson WHERE lesson_id = ?");
        $stmt->bind_param("i", $lessonId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
