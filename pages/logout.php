<?php
$_SESSION['login'] = $_SESSION['userinfo'] = '';
func_setcookie('remember', '');
redirect($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '/', 1);