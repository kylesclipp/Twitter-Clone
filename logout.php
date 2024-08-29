<?php
session_start();
session_unset();
session_destroy();
echo "Logging out...";
header("Location: login.php");
exit();
?>
