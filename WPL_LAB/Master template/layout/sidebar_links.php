<?php
$links = [];
switch($role){
case 'student':
$links=[
['id'=>'home','label'=>'Dashboard','href'=>'../../roles/student/dashboard.php'],
['id'=>'courses','label'=>'Courses','href'=>'#'],
['id'=>'attendance','label'=>'Attendance','href'=>'#'],
['id'=>'results','label'=>'Results','href'=>'#'],
['id'=>'timetable','label'=>'Timetable','href'=>'#'],
['id'=>'notifications','label'=>'Notifications','href'=>'../../public/notification.php']
];
break;
case 'teacher':
$links=[
['id'=>'home','label'=>'Dashboard','href'=>'../../roles/teacher/dashboard.php'],
['id'=>'my_courses','label'=>'My Courses','href'=>'#'],
['id'=>'notifications','label'=>'Notifications','href'=>'../../public/notification.php']
];
break;
case 'admin':
case 'superadmin':
$links=[
['id'=>'home','label'=>'Dashboard','href'=>'../../roles/admin/dashboard.php'],
['id'=>'manage_users','label'=>'Manage Users','href'=>'../../admin_tools/User/user_list.php'],
['id'=>'create_course','label'=>'Create Course','href'=>'../../admin_tools/create_course.php'],
['id'=>'manage_courses','label'=>'Manage Courses','href'=>'../../admin_tools/manage_courses.php'],
['id'=>'notifications','label'=>'Notifications','href'=>'../../public/notification.php']
];
break;
default:
$links=[['id'=>'home','label'=>'Dashboard','href'=>'#']];
}
foreach($links as $link){
$active=($active_page==$link['id'])?'active':'';
echo "<a href='{$link['href']}' class='list-group-item list-group-item-action $active'>{$link['label']}</a>";
}
// Logout always
echo '<a href="../../public/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>';
?>