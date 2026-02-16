<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	foreach ($data as $k=>$v) {
		$v['enabled'] = $v['enabled'] ? 1 : 0;
		if (DEMO) {
			unset($v['mode']);
			unset($v['param1']);
			unset($v['param2']);
		}

		$db->array2update("payment_methods", $v, "paymentid='".addslashes($k)."'");
	}

	$_SESSION['alerts'][] = array(
		'content'	=> 'Payment methods has been successfully updated'
	);

	redirect('/admin/payment');
}

$template['head_title'] = lng('Payment methods').' :: '.$template['head_title'];
$template['location'] .= ' &gt; '.lng('Payment methods');

$payment_methods = $db->all("SELECT * FROM payment_methods ORDER BY enabled DESC, orderby");
$template['payment_methods'] = $payment_methods;

$template['page'] = get_template_contents('admin/pages/payment.php');

$template['css'][] = 'admin_payment';
$template['js'][] = 'admin_payment';

?>
