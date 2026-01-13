<?php
/**
 * Assignment Form Partial - Collapsible assignment creation form
 * Variables available: $module
 */
?>
<div class="collapse mb-3" id="addAssignment<?= $module['module_id'] ?>">
    <div class="card bg-light">
        <div class="card-body">
            <h6><i class="bi bi-clipboard-check"></i> Create Assignment</h6>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="module_id" value="<?= $module['module_id'] ?>">
                
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="assignment_title" class="form-control form-control-sm" 
                               placeholder="Assignment Title *" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="max_marks" class="form-control form-control-sm" 
                               placeholder="Max Marks *" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="weightage" class="form-control form-control-sm" 
                               placeholder="Weight % *" step="0.1" min="0" max="100" required>
                    </div>
                    <div class="col-md-3">
                        <input type="datetime-local" name="due_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-1">
                        <div class="form-check mt-1">
                            <input type="checkbox" name="allow_late" class="form-check-input" 
                                   id="late<?= $module['module_id'] ?>">
                            <label class="form-check-label small" for="late<?= $module['module_id'] ?>">
                                Late?
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <textarea name="assignment_desc" class="form-control form-control-sm" rows="2" 
                                  placeholder="Instructions/Description"></textarea>
                    </div>
                    <div class="col-md-3">
                        <input type="file" name="assignment_file" class="form-control form-control-sm">
                        <small class="text-muted">Assignment template/instructions</small>
                    </div>
                    <div class="col-md-2">
                        <select name="assignment_resource_type" class="form-select form-select-sm">
                            <option value="pdf">PDF</option>
                            <option value="doc">DOC</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="create_assignment" class="btn btn-success btn-sm w-100">
                            Create
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
