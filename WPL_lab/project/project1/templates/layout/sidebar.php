<?php
// Make sure session variables are set before using them
$name = $name ?? 'Guest';
$role = $role ?? 'guest';
$user_code = $user_code ?? '';
$profile_image = $profile_image ?? null;

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set profile image path
// Priority: session profile image > $profile_image variable > default avatar
if (!empty($_SESSION['profile_image']) && file_exists($_SERVER['DOCUMENT_ROOT'].'/project1/public/uploads/profiles/' . $_SESSION['profile_image'])) {
    $profile_image_url = url('public/uploads/profiles/' . $_SESSION['profile_image']);
} elseif (!empty($profile_image) && file_exists($_SERVER['DOCUMENT_ROOT'].'/project1/public/uploads/profiles/' . $profile_image)) {
    $profile_image_url = url('public/uploads/profiles/' . $profile_image);
} else {
    $profile_image_url = url('public/male_avatar.jpeg');
}
?>

<div class="bg-light border-end" id="sidebar-wrapper">
    <div id="sidebar">
        <div class="user-profile text-center p-3">
            <img src="<?= $profile_image_url ?>" alt="User Avatar" 
                 class="rounded-circle mb-2" 
                 style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #007bff;">
            <h6 class="mb-0"><?= htmlspecialchars($name) ?></h6>
            <?php if ($role === 'student' && !empty($user_code)) : ?>
                <small class="text-muted"><?= htmlspecialchars($user_code) ?></small>
            <?php else: ?>
                <small class="text-muted"><?= ucfirst(htmlspecialchars($role)) ?></small>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-heading px-3 py-2">LMS - <?= ucfirst(htmlspecialchars($role)) ?> Portal</div>
        
        <div class="list-group list-group-flush">
            <?php include include_file('templates/layout/sidebar_links.php'); ?>
        </div>
    </div>
</div>
