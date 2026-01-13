<nav class="navbar navbar-light bg-light border-bottom">
<div class="container-fluid">
<button class="btn btn-outline-secondary" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>
<span class="navbar-brand ms-2">LMS Portal</span>
<ul class="navbar-nav ms-auto flex-row">
<?php if($role==='student'): ?><li class="nav-item me-3"><a class="nav-link" href="../../public/notification.php">🔔 Notifications</a></li><?php endif; ?>
<?php if(in_array($role,['admin','superadmin'])): ?><li class="nav-item me-3"><a class="nav-link" href="../../admin_tools/User/user_list.php">Manage Users</a></li><?php endif; ?>
<li class="nav-item"><a class="nav-link text-danger" href="../../public/logout.php">Logout</a></li>
<li class="nav-item me-3"><a class="nav-link" href="../../public/notification.php">🔔 Notifications</a></li>
</ul>
</div>
</nav>
<script src="../../js/script.js"></script>
