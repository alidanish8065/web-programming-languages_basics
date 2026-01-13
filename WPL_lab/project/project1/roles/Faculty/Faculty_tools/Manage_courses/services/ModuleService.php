<?php
/**
 * ModuleService - Handle module CRUD operations
 */
class ModuleService {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /**
     * Create a new module
     */
    public function create(array $data, int $offeringId): bool {
        $name = trim($data['module_name']);
        $desc = trim($data['description'] ?? '');
        $seq = (int)$data['sequence'];
        $moduleCode = 'MOD' . $seq;
        
        $stmt = $this->db->prepare(
            "INSERT INTO module 
             (offering_id, module_name, module_code, description, sequence_number, status)
             VALUES (?, ?, ?, ?, ?, 'active')"
        );
        $stmt->bind_param("isssi", $offeringId, $name, $moduleCode, $desc, $seq);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get all modules for a course offering
     */
    public function getByOfferingId(int $offeringId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM module WHERE offering_id = ? AND status = 'active' ORDER BY sequence_number"
        );
        $stmt->bind_param("i", $offeringId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    /**
     * Delete a module
     */
    public function delete(int $moduleId): bool {
        $stmt = $this->db->prepare("UPDATE module SET status = 'inactive' WHERE module_id = ?");
        $stmt->bind_param("i", $moduleId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
