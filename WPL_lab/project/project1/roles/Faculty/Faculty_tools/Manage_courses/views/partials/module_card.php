<?php
/**
 * Module Card Partial - Displays a single module with lessons and assignments
 * Variables available: $module, $nextLessonSeq, $offeringId, $gradingService
 */
?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-folder"></i> Module <?= $module['sequence_number'] ?>: 
                <?= htmlspecialchars($module['module_name']) ?>
            </h6>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addLesson<?= $module['module_id'] ?>">
                    <i class="bi bi-plus"></i> Lesson
                </button>
                <button class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#addAssignment<?= $module['module_id'] ?>">
                    <i class="bi bi-plus"></i> Assignment
                </button>
            </div>
        </div>
        <?php if ($module['description']): ?>
            <small class="text-muted"><?= htmlspecialchars($module['description']) ?></small>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <?php include __DIR__ . '/lesson_form.php'; ?>
        <?php include __DIR__ . '/assignment_form.php'; ?>
        
        <!-- Lessons List -->
        <?php if (!empty($module['lessons'])): ?>
            <h6 class="mb-2"><i class="bi bi-list-ul"></i> Lessons</h6>
            <div class="list-group mb-3">
                <?php foreach ($module['lessons'] as $lesson): 
                    $resources = $lesson['resources'] ? explode('|||', $lesson['resources']) : [];
                    $icons = [
                        'video' => '<i class="bi bi-camera-video text-danger"></i>',
                        'text' => '<i class="bi bi-file-text text-primary"></i>',
                        'slides' => '<i class="bi bi-file-slides text-warning"></i>',
                        'live' => '<i class="bi bi-broadcast text-success"></i>'
                    ];
                ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <?= $icons[$lesson['lesson_type']] ?? '<i class="bi bi-file"></i>' ?>
                                <strong class="ms-2"><?= htmlspecialchars($lesson['lesson_title']) ?></strong>
                                <?php if ($lesson['scheduled_start']): ?>
                                    <small class="text-muted d-block ms-4">
                                        <i class="bi bi-calendar-event"></i> 
                                        <?= date('M d, Y g:i A', strtotime($lesson['scheduled_start'])) ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($lesson['description']): ?>
                                    <small class="text-muted d-block ms-4"><?= htmlspecialchars($lesson['description']) ?></small>
                                <?php endif; ?>
                                <?php if ($resources): ?>
                                    <div class="mt-1 ms-4">
                                        <?php foreach ($resources as $res): 
                                            list($type, $name, $url) = explode(':', $res, 3);
                                            $resIcons = [
                                                'pdf' => 'file-pdf', 'video' => 'camera-video',
                                                'doc' => 'file-word', 'ppt' => 'file-ppt', 'link' => 'link-45deg'
                                            ];
                                        ?>
                                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" 
                                               class="badge bg-secondary text-decoration-none me-1">
                                                <i class="bi bi-<?= $resIcons[$type] ?? 'paperclip' ?>"></i> 
                                                <?= htmlspecialchars($name) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="lesson_id" value="<?= $lesson['lesson_id'] ?>">
                                <button type="submit" name="delete_lesson" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Delete this lesson?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Assignments List -->
        <?php if (!empty($module['assignments'])): ?>
            <h6 class="mb-2"><i class="bi bi-clipboard-check"></i> Assignments</h6>
            <?php foreach ($module['assignments'] as $assignment): ?>
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($assignment['assignment_title']) ?></strong>
                                <small class="d-block text-muted">
                                    Max: <?= $assignment['max_marks'] ?> | 
                                    Weight: <?= $assignment['weightage'] ?>% | 
                                    Due: <?= date('M d, Y g:i A', strtotime($assignment['due_date'])) ?>
                                </small>
                                <?php if ($assignment['description']): ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($assignment['description']) ?></small>
                                <?php endif; ?>
                                <?php if ($assignment['pending'] > 0): ?>
                                    <span class="badge bg-warning text-dark"><?= $assignment['pending'] ?> pending</span>
                                    <button class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" 
                                            data-bs-target="#grade<?= $assignment['assignment_id'] ?>">
                                        Grade Now →
                                    </button>
                                    
                                    <?php $subs = $gradingService->getPendingSubmissions($assignment['assignment_id'], 3); ?>
                                    <div class="collapse mt-2 bg-light p-2 rounded" id="grade<?= $assignment['assignment_id'] ?>">
                                        <?php foreach ($subs as $sub): ?>
                                            <form method="POST" class="row g-2 align-items-center mb-1">
                                                <input type="hidden" name="submission_id" value="<?= $sub['submission_id'] ?>">
                                                <div class="col-md-4">
                                                    <small><strong><?= htmlspecialchars($sub['student_name']) ?></strong></small><br>
                                                    <small class="text-muted"><?= date('M d, g:i A', strtotime($sub['submitted_at'])) ?></small>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" name="marks" class="form-control form-control-sm"
                                                           placeholder="Marks" max="<?= $assignment['max_marks'] ?>" min="0" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="feedback" class="form-control form-control-sm" 
                                                           placeholder="Feedback (optional)">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" name="grade_submission" class="btn btn-sm btn-success w-100">
                                                        <i class="bi bi-check-circle"></i> Grade
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endforeach; ?>
                                        <?php if (count($subs) >= 3): ?>
                                            <div class="text-center mt-2">
                                                <a href="../grade_assignment.php?id=<?= $assignment['assignment_id'] ?>" class="btn btn-sm btn-link">
                                                    View All Submissions →
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="assignment_id" value="<?= $assignment['assignment_id'] ?>">
                                <button type="submit" name="delete_assignment" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Delete this assignment?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
