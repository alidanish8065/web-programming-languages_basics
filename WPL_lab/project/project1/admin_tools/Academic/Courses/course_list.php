<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin'])) {
    die('Access denied.');
}

$active_page = 'courses';

// Fetch all courses with department info and prerequisite count
$sql = "
    SELECT 
        c.*,
        d.department_name,
        d.department_code,
        f.faculty_name,
        COUNT(DISTINCT co.offering_id) as offering_count,
        COUNT(DISTINCT cp.prerequisite_course_id) as prereq_count,
        GROUP_CONCAT(DISTINCT c2.course_code ORDER BY c2.course_code SEPARATOR ', ') as prereq_codes
    FROM course c
    JOIN department d ON c.department_id = d.department_id
    JOIN faculty f ON d.faculty_id = f.faculty_id
    LEFT JOIN course_offering co ON c.course_id = co.course_id
    LEFT JOIN course_prerequisite cp ON c.course_id = cp.course_id
    LEFT JOIN course c2 ON cp.prerequisite_course_id = c2.course_id
    WHERE c.is_deleted = FALSE
    GROUP BY c.course_id, c.course_name, c.course_code, c.department_id, c.credit_hrs, 
             c.course_type, c.recommended_semester, c.description, c.created_at, c.updated_at, c.is_deleted,
             d.department_name, d.department_code, f.faculty_name
    ORDER BY d.department_name, c.course_code
";
$result = $conn->query($sql);
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-journal-text"></i> Course Management</h4>
        <a href="create_course.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Course
        </a>
    </div>

    <?php if (empty($courses)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No courses found. Create your first course to get started.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Filter/Search Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search courses...">
                    </div>
                    <div class="col-md-3">
                        <select id="filterType" class="form-select">
                            <option value="">All Types</option>
                            <option value="core">Core</option>
                            <option value="elective">Elective</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-end">
                        <small class="text-muted">
                            Total Courses: <strong><?= count($courses) ?></strong>
                        </small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="coursesTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 100px;">Code</th>
                                <th>Course Name</th>
                                <th style="width: 150px;">Department</th>
                                <th style="width: 80px;">Credits</th>
                                <th style="width: 100px;">Type</th>
                                <th style="width: 150px;">Prerequisites</th>
                                <th style="width: 100px;">Offerings</th>
                                <th style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; foreach ($courses as $course): ?>
                                <tr data-type="<?= htmlspecialchars($course['course_type']) ?>">
                                    <td><?= $serial++ ?></td>
                                    <td>
                                        <code class="text-primary">
                                            <strong><?= htmlspecialchars($course['course_code']) ?></strong>
                                        </code>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($course['course_name']) ?></strong>
                                        <?php if (!empty($course['description'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($course['description'], 0, 60)) ?>
                                                <?= strlen($course['description']) > 60 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($course['department_code']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $course['credit_hrs'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $course['course_type'] === 'core' ? 'primary' : 'info' ?>">
                                            <?= ucfirst($course['course_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($course['prereq_codes'])): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?= $course['prereq_count'] ?> course<?= $course['prereq_count'] > 1 ? 's' : '' ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php 
                                                $prereqs = explode(', ', $course['prereq_codes']);
                                                echo htmlspecialchars(implode(', ', array_slice($prereqs, 0, 2)));
                                                if (count($prereqs) > 2) echo '...';
                                                ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($course['offering_count'] > 0): ?>
                                            <span class="badge bg-info"><?= $course['offering_count'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Quick View Button (Modal) -->
                                            <button type="button" 
                                                    class="btn btn-info"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal"
                                                    onclick="loadCourseDetails(<?= $course['course_id'] ?>)"
                                                    title="Quick View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <!-- Edit Button -->
                                            <a href="edit_course.php?id=<?= $course['course_id'] ?>" 
                                               class="btn btn-warning"
                                               title="Edit Course">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <!-- Delete Button -->
                                            <a href="delete_course.php?id=<?= $course['course_id'] ?>" 
                                               class="btn btn-danger"
                                               title="Delete Course"
                                               onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($course['course_code']) ?>?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-journal-text"></i> Course Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="editCourseBtn" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit Course
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const table = document.getElementById('coursesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const typeFilter = filterType.value.toLowerCase();
        
        for (let row of rows) {
            const text = row.textContent.toLowerCase();
            const type = row.getAttribute('data-type');
            
            const matchesSearch = text.includes(searchTerm);
            const matchesType = !typeFilter || type === typeFilter;
            
            row.style.display = matchesSearch && matchesType ? '' : 'none';
        }
    }
    
    if (searchInput) searchInput.addEventListener('keyup', filterTable);
    if (filterType) filterType.addEventListener('change', filterTable);
});

// Load course details in modal
function loadCourseDetails(courseId) {
    const modalContent = document.getElementById('modalContent');
    const editBtn = document.getElementById('editCourseBtn');
    
    // Update edit button link
    editBtn.href = `edit_course.php?id=${courseId}`;
    
    // Show loading spinner
    modalContent.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    // Fetch course details via AJAX
    fetch(`get_course_details.php?id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const course = data.course;
                const prerequisites = data.prerequisites;
                
                let prereqHtml = '<span class="badge bg-secondary">None</span>';
                if (prerequisites.length > 0) {
                    prereqHtml = '<ul class="list-group list-group-flush">';
                    prerequisites.forEach(prereq => {
                        prereqHtml += `
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span>
                                    <code>${prereq.course_code}</code> - ${prereq.course_name}
                                </span>
                                <span class="badge bg-${prereq.is_mandatory ? 'danger' : 'warning'}">
                                    ${prereq.is_mandatory ? 'Mandatory' : 'Recommended'}
                                </span>
                            </li>
                        `;
                    });
                    prereqHtml += '</ul>';
                }
                
                modalContent.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h4 class="mb-1">${course.course_code}</h4>
                            <h6 class="text-muted">${course.course_name}</h6>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-${course.course_type === 'core' ? 'primary' : 'info'} fs-6">
                                ${course.course_type.charAt(0).toUpperCase() + course.course_type.slice(1)}
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Department:</strong><br>
                            <span class="badge bg-light text-dark border">${course.department_code}</span>
                            ${course.department_name}
                        </div>
                        <div class="col-md-3">
                            <strong>Credit Hours:</strong><br>
                            <span class="badge bg-secondary">${course.credit_hrs}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Semester:</strong><br>
                            ${course.recommended_semester > 0 ? 
                                `<span class="badge bg-success">Sem ${course.recommended_semester}</span>` : 
                                '<span class="badge bg-secondary">N/A</span>'}
                        </div>
                    </div>
                    
                    ${course.description ? `
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="mt-2 text-muted">${course.description}</p>
                        </div>
                    ` : ''}
                    
                    <div class="mb-3">
                        <strong>Prerequisites:</strong>
                        <div class="mt-2">${prereqHtml}</div>
                    </div>
                    
                    <div class="text-muted">
                        <small><i class="bi bi-calendar"></i> Created: ${new Date(course.created_at).toLocaleDateString()}</small>
                    </div>
                `;
            } else {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> ${data.message || 'Failed to load course details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error loading course details
                </div>
            `;
        });
}
</script>

<?php
$content = ob_get_clean();
$page_title = "Course Management - LMS";
require_once '../../../templates/layout/master_base.php';
?>