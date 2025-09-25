<?php
// logout.php - Updated for v0.2.0
session_start();
session_destroy();
header('Location: public/index.php');
exit;
?>