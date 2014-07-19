<?php

session_start();
$_SESSION['uid'] = -1;
$_SESSION['time'] = 0;
session_destroy();

include('function.php');
scan_error_exit(SCAN_WX_STATUS_SUCCESS);

?>
