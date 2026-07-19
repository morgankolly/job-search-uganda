<?php
session_start();
?>

<h2>My Profile</h2>

<p>Name: <?php echo $_SESSION['name'] ?? 'Guest'; ?></p>
<p>Role: <?php echo $_SESSION['role'] ?? 'User'; ?></p>
<p>Profile Picture: <img src="<?php echo $_SESSION['profile'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile"></p>