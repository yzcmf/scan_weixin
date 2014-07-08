<?php

session_start();
$_SESSION['uid'] = -1;
$_SESSION['time'] = 0;
session_destroy();

?>
