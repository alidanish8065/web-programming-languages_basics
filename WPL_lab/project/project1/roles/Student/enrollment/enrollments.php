<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // If AJAX, return JSON; otherwise redirect
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    header('Location: ' . url('public/login.php'));
    exit;
}

$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    header('Location: ' . url('public/login.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| AJAX handler (POST)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    // Ensure JSON response
    header('Content-Type: application/json');

    $offering_id = intval($_POST['offering_id'] ?? 0);
    if ($offering_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid offering id']);
        exit;
    }

    // Check already enrolled
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM enrollment WHERE student_id = ? AND offering_id = ?");
    $stmt->bind_param("ii", $student_id, $offering_id);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmt->close();

    if ($cnt > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Already enrolled.']);
        exit;
    }

    // Get course info (credit hrs, max_enrollment, current enrolled_count)
    $stmt = $conn->prepare("
        SELECT c.credit_hrs, co.max_enrollment,
               (SELECT COUNT(*) FROM enrollment WHERE offering_id = co.offering_id) AS enrolled_count
        FROM course_offering co
        JOIN course c ON co.course_id = c.course_id
        WHERE co.offering_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $offering_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$course) {
        echo json_encode(['status' => 'error', 'message' => 'Course not found.']);
        exit;
    }

    // Check capacity
    if (!empty($course['max_enrollment']) && $course['enrolled_count'] >= $course['max_enrollment']) {
        echo json_encode(['status' => 'error', 'message' => 'Course is full.']);
        exit;
    }

    // Insert enrollment (basic insert)
    $stmt = $conn->prepare("INSERT INTO enrollment (student_id, offering_id, credit_hrs, status) VALUES (?, ?, ?, 'enrolled')");
    $credit_hrs = $course['credit_hrs'] ?? 0;
    $stmt->bind_param("iii", $student_id, $offering_id, $credit_hrs);

    if ($stmt->execute()) {
        $stmt->close();
        // Return success with new enrolled_count (increment previous count by 1)
        $new_count = intval($course['enrolled_count']) + 1;
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully enrolled!',
            'offering_id' => $offering_id,
            'enrolled_count' => $new_count
        ]);
        exit;
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Enrollment failed.']);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| GET: render page (non-AJAX)
|--------------------------------------------------------------------------
*/

// Get student's program & semester
$stmt = $conn->prepare("SELECT program_id, current_semester FROM student WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

// Fetch available courses (not enrolled yet)
$stmt = $conn->prepare("
    SELECT co.offering_id,
           c.course_id,
           c.course_code,
           c.course_name,
           c.credit_hrs,
           c.course_type,
           co.academic_year,
           co.semester,
           co.term,
           co.max_enrollment,
           (SELECT COUNT(*) FROM enrollment WHERE offering_id = co.offering_id) AS enrolled_count
    FROM course_offering co
    JOIN course c ON co.course_id = c.course_id
    WHERE c.department_id = (
        SELECT department_id FROM program WHERE program_id = ?
    )
    AND co.offering_id NOT IN (
        SELECT offering_id FROM enrollment WHERE student_id = ?
    )
    ORDER BY c.course_code
");
$program_id = $student_info['program_id'] ?? null;
$stmt->bind_param("ii", $program_id, $student_id);
$stmt->execute();
$available_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>
<h3 class="mb-3">➕ Enroll in Courses</h3>

<?php if (empty($available_courses)): ?>
    <div class="alert alert-info">No courses available for enrollment at this time.</div>
<?php else: ?>
    <div class="row" id="courses-container">
        <?php foreach ($available_courses as $course):
            $is_full = $course['max_enrollment'] && $course['enrolled_count'] >= $course['max_enrollment'];
            $off_id = (int)$course['offering_id'];
        ?>
            <div class="col-md-6 mb-3" id="course-<?= $off_id ?>">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5><?= htmlspecialchars($course['course_code']) ?></h5>
                            <span class="badge bg-info"><?= htmlspecialchars($course['credit_hrs']) ?> Credits</span>
                        </div>

                        <h6 class="text-muted"><?= htmlspecialchars($course['course_name']) ?></h6>

                        <p class="small mb-2">
                            <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($course['course_type'])) ?></span>
                            <?= htmlspecialchars($course['term']) ?> <?= htmlspecialchars($course['academic_year']) ?> (Sem <?= htmlspecialchars($course['semester']) ?>)
                        </p>

                        <?php if ($course['max_enrollment']): ?>
                            <small class="text-muted">
                                Seats: <span id="seat-count-<?= $off_id ?>"><?= htmlspecialchars($course['enrolled_count']) ?></span>/<?= htmlspecialchars($course['max_enrollment']) ?>
                            </small>
                        <?php endif; ?>

                        <div class="mt-2">
                            <button class="btn btn-primary btn-sm w-100 enroll-btn"
                                    data-offering="<?= $off_id ?>"
                                    <?= $is_full ? 'disabled' : '' ?>>
                                <?= $is_full ? 'Course Full' : 'Enroll Now' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function() {
    // Use current page URL as AJAX endpoint to avoid path issues
    const endpoint = window.location.href.split('#')[0];

    function safeText(n) { return String(n); }

    document.querySelectorAll('.enroll-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const offeringId = this.dataset.offering;
            const button = this;
            button.disabled = true;
            const originalText = button.innerText;
            button.innerText = 'Enrolling...';

            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=enroll&offering_id=${encodeURIComponent(offeringId)}`
            })
            .then(async res => {
                const text = await res.text();
                // Try parse JSON, otherwise throw
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    button.innerText = 'Enrolled ✅';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-success');
                    // update seat count if present
                    if (data.enrolled_count !== undefined) {
                        const seatElem = document.getElementById(`seat-count-${offeringId}`);
                        if (seatElem) seatElem.innerText = safeText(data.enrolled_count);
                    } else {
                        const seatElem = document.getElementById(`seat-count-${offeringId}`);
                        if (seatElem) seatElem.innerText = parseInt(seatElem.innerText || '0') + 1;
                    }
                } else {
                    button.disabled = false;
                    button.innerText = originalText;
                    alert(data.message || 'Enrollment failed');
                }
            })
            .catch(err => {
                console.error(err);
                button.disabled = false;
                button.innerText = originalText;
                alert('An error occurred. Try again.');
            });
        });
    });
})();
</script>

<?php
$content = ob_get_clean();
$page_title = 'Enroll in Courses';
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/templates/layout/master_base.php';
$conn->close();
