<?php
/**
 * Lesson Form Partial - Collapsible lesson creation form
 * Variables available: $module, $nextLessonSeq
 */
?>
<div class="collapse mb-3" id="addLesson<?= $module['module_id'] ?>">
    <div class="card bg-light">
        <div class="card-body">
            <h6><i class="bi bi-file-earmark-text"></i> Create Lesson</h6>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="module_id" value="<?= $module['module_id'] ?>">
                <input type="hidden" name="sequence" value="<?= $nextLessonSeq ?>">
                
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="lesson_title" class="form-control form-control-sm" 
                               placeholder="Lesson Title *" required>
                    </div>
                    <div class="col-md-2">
                        <select name="lesson_type" class="form-select form-select-sm" required>
                            <option value="video">Video</option>
                            <option value="text">Text</option>
                            <option value="slides">Slides</option>
                            <option value="live">Live Session</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="datetime-local" name="scheduled_start" class="form-control form-control-sm">
                        <small class="text-muted">For live sessions</small>
                    </div>
                    <div class="col-md-3">
                        <textarea name="lesson_desc" class="form-control form-control-sm" rows="1" 
                                  placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                
                <div class="row g-2 mt-2">
                    <div class="col-md-4">
                        <label class="form-label small">Upload File</label>
                        <input type="file" name="resource_file" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">File Type</label>
                        <select name="resource_type" class="form-select form-select-sm">
                            <option value="pdf">PDF</option>
                            <option value="video">Video</option>
                            <option value="doc">DOC</option>
                            <option value="ppt">PPT</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">OR URL</label>
                        <input type="url" name="resource_url" class="form-control form-control-sm" 
                               placeholder="https://youtube.com/...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">URL Name</label>
                        <input type="text" name="url_name" class="form-control form-control-sm" 
                               placeholder="e.g., Lecture Video">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">&nbsp;</label>
                        <button type="submit" name="create_lesson" class="btn btn-success btn-sm w-100">
                            Create
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
