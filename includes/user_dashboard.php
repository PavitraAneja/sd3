<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<div id="menuIcon" onclick="toggleDashboard()" style="position: fixed; top: 10px; right: 10px; cursor: pointer; z-index: 999;">
    &#9776;
</div>


<div id="userDashboard" style="display: none; position: fixed; top: 40px; right: 10px; background-color: white; border: 1px solid #ccc; padding: 15px; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="my_saves.php">Saved Listings</a><br>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a><br>
        <a href="register.php">Register</a>
    <?php endif; ?>
</div>

<script>
function toggleDashboard() {
    const menu = document.getElementById('userDashboard');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}
document.addEventListener('click', function(event) {
    const dashboard = document.getElementById('userDashboard');
    const icon = document.getElementById('menuIcon');
    if (!dashboard.contains(event.target) && !icon.contains(event.target)) {
        dashboard.style.display = 'none';
    }
});
</script>