<?php
/**
 * Course Dashboard View - Main course management interface
 * Variables available: $course, $modules, $nextModuleSeq, $offeringId, $msg, $error, $gradingService
 */
$active_page = 'my_courses';
ob_start();
?>

<div class="container-fluid">
    <div class="mb-3">
        <a href="<?= dirname($_SERVER['PHP_SELF']) ?>/index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> My Courses
        </a>
        <a href="../Courses/course_student.php?id=<?= $offeringId ?>" class="btn btn-outline-primary">
            <i class="bi bi-people"></i> Students
        </a>
        <a href="../grade_book.php?id=<?= $offeringId ?>" class="btn btn-outline-success">
            <i class="bi bi-bar-chart"></i> Gradebook
        </a>
        <a href="../mark_attendance.php?id=<?= $offeringId ?>" class="btn btn-outline-info">
            <i class="bi bi-calendar-check"></i> Attendance
        </a>
    </div>
    
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="bi bi-book"></i> <?= htmlspecialchars($course['course_code']) ?></h5>
                <small><?= htmlspecialchars($course['course_name']) ?> | <?= htmlspecialchars($course['department_name']) ?></small>
            </div>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createModuleModal">
                <i class="bi bi-plus-circle"></i> New Module
            </button>
        </div>
    </div>
    
    <?php if (empty($modules)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No modules yet. Create your first module to get started!
        </div>
    <?php else: ?>
        <?php foreach ($modules as $module): 
            $nextLessonSeq = empty($module['lessons']) ? 1 : max(array_column($module['lessons'], 'sequence_number')) + 1;
        ?>
            <?php include __DIR__ . '/partials/module_card.php'; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/module_modal.php'; ?>

<?php
$content = ob_get_clean();
$page_title = 'Manage Course - ' . htmlspecialchars($course['course_code']);
require_once include_file('templates/layout/master_base.php');
?>
