<?php
$links = [];

switch($role) {
    case 'student':
        $links = [
            ['id' => 'home', 'label' => 'Dashboard', 'href' => url('roles/student/dashboard.php')],
            ['id' => 'profile', 'label' => 'Profile', 'href' => url('public/profile.php')],
            ['id' => 'attendance', 'label' => 'Attendance', 'href' => url('roles/student/attendance.php')],
            ['id' => 'results', 'label' => 'Results', 'href' => url('roles/student/result_timetable/result.php')],
            ['id' => 'enrollments', 'label' => 'Enrollments', 'href' => url('roles/student/enrollment/enrollments.php')],
            ['id' => 'invoices', 'label' => 'Invoices & Payments', 'href' => url('roles/student/invoice/invoices.php')],
            ['id' => 'notifications', 'label' => 'Notifications', 'href' => url('public/notification/notification_list.php')]
        ];
        break;
    
    case 'faculty':
        $links = [
            ['id' => 'home', 'label' => 'Dashboard', 'href' => url('roles/Faculty/dashboard.php')],
            ['id' => 'profile', 'label' => 'Profile', 'href' => url('public/profile.php')],
            ['id' => 'my_courses', 'label' => 'My Courses', 'href' => url('roles/faculty/Faculty_tools/manage_course.php')],
            ['id' => 'grades', 'label' => 'Grades', 'href' => url('roles/Faculty/Faculty_tools/grade_book.php')],
            ['id' => 'notifications', 'label' => 'Notifications', 'href' => url('public/notification/notification_list.php')]
        ];
        break;
    
    case 'admin':
    case 'superadmin':
        $links = [
            ['id' => 'home', 'label' => 'Dashboard', 'href' => url('roles/admin/dashboard.php')],
            ['id' => 'profile', 'label' => 'Profile', 'href' => url('public/profile.php')],
            ['id' => 'manage_users', 'label' => 'Manage Users', 'href' => url('admin_tools/User/user_list.php')],
            ['id' => 'faculty', 'label' => 'Faculty', 'href' => url('admin_tools/Academic/Faculty/faculty_list.php')],
            ['id' => 'departments', 'label' => 'Departments', 'href' => url('admin_tools/Academic/Department/department_list.php')],
            ['id' => 'programs', 'label' => 'Programs', 'href' => url('admin_tools/Academic/Program/program_list.php')],
            ['id' => 'courses', 'label' => 'Courses', 'href' => url('admin_tools/Academic/Courses/course_list.php')],
            ['id' => 'offerings', 'label' => 'Course Offerings', 'href' => url('admin_tools/Academic/Courses/offering/offering_list.php')],
            ['id' => 'notifications', 'label' => 'Notifications', 'href' => url('public/notification/notification_list.php')]
        ];
        break;
    
    default:
        $links = [
            ['id' => 'home', 'label' => 'Dashboard', 'href' => '#']
        ];
}

// Render links
foreach ($links as $link) {
    $active = (isset($active_page) && $active_page === $link['id']) ? 'active' : '';
    $href = htmlspecialchars($link['href']);
    $label = htmlspecialchars($link['label']);
    echo "<a href='{$href}' class='list-group-item list-group-item-action {$active}'>{$label}</a>";
}

// Logout always appears at the bottom
echo '<a href="' . url('public/login_and_authentication/logout.php') . '" class="list-group-item list-group-item-action text-danger">Logout</a>';
?>