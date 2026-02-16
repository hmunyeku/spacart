<?php
extract($_GET, EXTR_SKIP);
if ($db->field("SELECT COUNT(*) FROM subscribers WHERE email='".addslashes($email)."'")) {
	exit(lng('You are already subscribed'));
} else {
	$insert = array(
		'title'			=> $title,
		'name'			=> $name,
		'email'			=> $email,
		'date'			=> time()
	);

	$db->array2insert('subscribers', $insert);

	exit(lng('Thank you for your subscription'));
}