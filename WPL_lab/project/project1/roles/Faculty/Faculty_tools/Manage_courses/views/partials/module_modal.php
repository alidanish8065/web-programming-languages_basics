<?php
/**
 * Module Modal Partial - Bootstrap modal for creating modules
 * Variables available: $nextModuleSeq
 */
?>
<div class="modal fade" id="createModuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus"></i> Create New Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Module Name <span class="text-danger">*</span></label>
                        <input type="text" name="module_name" class="form-control" 
                               placeholder="e.g., Introduction to Course" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Brief description of this module..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sequence Number</label>
                        <input type="number" name="sequence" class="form-control" 
                               value="<?= $nextModuleSeq ?>" min="1" required>
                        <small class="text-muted">Order in which this module appears</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_module" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Module
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
