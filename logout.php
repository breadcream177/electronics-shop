<?php
session_start();
session_destroy();

header("Location: /electronics_shop/index.php");
exit;
?>