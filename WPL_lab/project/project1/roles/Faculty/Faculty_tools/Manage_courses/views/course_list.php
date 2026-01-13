<?php
/**
 * Course List View - Shows all courses for a teacher
 * Variables available: $courses
 */
$active_page = 'my_courses';
ob_start();
?>
<div class="container-fluid">
    <h3><i class="bi bi-journal-text"></i> My Courses</h3>
    <p class="text-muted">Select a course to manage</p>
    
    <?php if (empty($courses)): ?>
        <div class="alert alert-info">No courses assigned yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($courses as $course): ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?= htmlspecialchars($course['course_code']) ?></h5>
                            <small><?= htmlspecialchars($course['department_name']) ?></small>
                        </div>
                        <div class="card-body">
                            <h6><?= htmlspecialchars($course['course_name']) ?></h6>
                            <p class="small text-muted mb-2">
                                <?= $course['term'] ?> <?= $course['academic_year'] ?> | 
                                Sem <?= $course['semester'] ?> | 
                                <?= $course['credit_hrs'] ?> Credits
                            </p>
                            <p class="mb-3"><strong><?= $course['student_count'] ?></strong> students enrolled</p>
                            <a href="?id=<?= $course['offering_id'] ?>" class="btn btn-primary w-100">
                                Manage Course →
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$page_title = 'My Courses';
require_once include_file('templates/layout/master_base.php');
?>
