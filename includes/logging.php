<?php
set_error_handler('errorHandler');
register_shutdown_function('errorShutdown');
ini_set('log_errors', 1);

function errorHandler($n, $m, $f, $l) {
	global $_SERVER, $_SESSION, $current_location;
    if (!(error_reporting() & $n)) {
    	return;
	}

	$redirect = false;
	if ($n == E_USER_ERROR) {
		$string = 'Error: '.$m.'. File: '.$f.'. Line: '.$l."\n";
		$redirect = true;
		$file_name = 'php';
	} elseif ($n == E_USER_WARNING) {
		$string = 'Warning: '.$m.'. File: '.$f.'. Line: '.$l."\n";
		$file_name = 'other';
	} elseif ($n == E_USER_NOTICE) {
		$string = 'Notice: '.$m.'. File: '.$f.'. Line: '.$l."\n";
		$file_name = 'other';
	} else {
		return;
	}

	$error = $string;

	$string = date('H:i:s j.n.y').': '.$string;

	$string .= "\n\nREQUEST_URI: ".$_SERVER['REQUEST_URI']."\n";

	$tmp = debug_backtrace();
	foreach ($tmp as $v)
		$string .= "\n".$v['file'].':'.$v['line'];

	$string .= "\n\n---\n\n";
	$fp = fopen(SITE_ROOT.'/var/log/'.$file_name.'-'.date('m-d-Y').'.log', 'a+');
	fputs($fp, $string);
	fclose($fp);
	if (ADMIN_AREA || DEVELOPMENT)
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=>lng('PHP error happens.').'<br />'.str_replace("\n", "<br />", $error)
		);

	if ($redirect) {
		if (ADMIN_AREA)
			header('Location: '.$current_location.'/admin');
		else
			header('Location: '.$current_location);

		exit;
	}
}

function errorShutdown() {
	global $_SERVER, $_SESSION, $current_location;
	$lasterror = error_get_last();
	if ($lasterror['type'] == '4')
		$error = 'Parse Error: '.$lasterror['message'].'. File: '.$lasterror['file'].'. Line: '.$lasterror['line']."\n";
	elseif ($lasterror['type'] == '1')
		$error = 'Fatal Error: '.$lasterror['message'].'. File: '.$lasterror['file'].'. Line: '.$lasterror['line']."\n";
	elseif ($lasterror['type'] == '64')
		$error = 'Error: '.$lasterror['message'].'. File: '.$lasterror['file'].'. Line: '.$lasterror['line']."\n";
	else
		return;

	$string = date('H:i:s j.n.y').': '.$error;

	$string .= "\nREQUEST_URI: ".$_SERVER['REQUEST_URI']."\n";
	echo '<pre>';
	echo $string;

	$string .= "\n---\n\n";
	$fp = fopen(SITE_ROOT.'/var/log/php-'.date('m-d-Y').'.log', 'a+');
	fputs($fp, $string);
	fclose($fp);
}

function log_sql($string) {
	global $_SERVER, $_SESSION, $current_location;

	$error = $string;

	$string = date('H:i:s j.n.y').': '.$string;

	$string .= "\n\n".$_SERVER['REQUEST_URI']."\n";

	$tmp = debug_backtrace();
	foreach ($tmp as $v)
		$string .= "\n".$v['file'].':'.$v['line'];

	$string .= "\n\n---\n\n";

	$fn = SITE_ROOT . '/var/log/sql-'.date('m-d-Y').'.log';
	$string .= file_get_contents($fn);
	file_put_contents($fn, $string);
	if (ADMIN_AREA || DEVELOPMENT)
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=>lng('MySQL error happens.').'<br />'.str_replace("\n", "<br />", $error)
		);

	if (ADMIN_AREA)
		header('Location: '.$current_location.'/admin');
	else
		header('Location: '.$current_location);

	exit;
}