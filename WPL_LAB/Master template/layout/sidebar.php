<div id="sidebar">
    <div class="user-profile text-center p-3">
        <img src="../../public/male_avatar.jpeg" alt="User Avatar" class="rounded-circle mb-2">
        <h6 class="mb-0"><?= htmlspecialchars($name) ?></h6>
        <?php if ($role === 'student') : ?>
            <small class="text-muted"><?= htmlspecialchars($student_id) ?></small>
        <?php endif; ?>
    </div>

    <div class="sidebar-heading px-3 py-2">LMS – <?= ucfirst($role) ?> Portal</div>
    <div class="list-group list-group-flush">
        <a href="#" class="list-group-item list-group-item-action active">Dashboard</a>

        <?php if ($role === 'admin' || $role === 'superadmin'): ?>
            <a class="list-group-item list-group-item-action" href="../../admin_tools/User/user_list.php">Manage Users</a>
            <a href="../../admin_tools/create_course.php" class="list-group-item list-group-item-action">Create Course</a>
            <a href="../../admin_tools/manage_courses.php" class="list-group-item list-group-item-action">Manage Courses</a>
            <a href="../../public/notification.php" class="list-group-item list-group-item-action">Notifications</a>
        <?php endif; ?>

        <a href="../../public/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
    </div>
</div>