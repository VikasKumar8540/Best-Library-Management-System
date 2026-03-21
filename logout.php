<?php
require_once 'includes/config.php';
session_destroy();
redirect(APP_URL . '/index.php');
?>
