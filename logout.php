<?php
require 'constants.php';
require 'session_handler.php';

logout();
header('Location: index.php');
exit();
?>
