<?php
// admin/logout.php - Updated for v0.1.0
session_start();
unset($_SESSION['admin_id']);
header('Location: login.php');
exit;
?>