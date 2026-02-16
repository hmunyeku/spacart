<?php
use Dompdf\Dompdf;

session_start();
q_load('product', 'order');
if (!$_SESSION['invoices']) {
	$_SESSION['invoices'] = array();
}

$found = false;
if ($userinfo['usertype'] == 'A') {
	$found = true;
} elseif (in_array($get['1'], $_SESSION['invoices'])) {
	$found = true;
} elseif ($login) {
	$check_order = $db->row("SELECT * FROM orders WHERE orderid='".addslashes($get['1'])."' AND userid='".$login."'");
	if ($check_order)
		$found = true;
} elseif ($_GET['token']) {
	$check_order = $db->row("SELECT * FROM orders WHERE orderid='".addslashes($get['1'])."' AND token='".addslashes($_GET['token'])."'");
	if ($check_order) {
		$_SESSION['invoices'][] = addslashes($get['1']);
		redirect('/invoice/'.$get['1']);
	}
}

if (!$found) {
	$_SESSION['alerts'][] = array(
		'type'		=> 'e',
		'content'	=> lng('You dont have access to this invoice')
	);

	redirect('/');
} elseif ($_GET['token']) {
	redirect('/invoice/'.$get['1']);
}

$orderinfo = func_orderinfo(addslashes($get['1']));
if ($orderinfo) {
	$template['order'] = $orderinfo['order'];
	$template['products'] = $orderinfo['products'];
} else
	redirect('/');

$template['no_left_menu'] = 'Y';

$template['head_title'] = lng('Order #').$get['1'].'. '.$template['head_title'];
$template['css'][] = 'invoice';
$template['js'][] = 'invoice';
$template['pdf_invoice'] = '1';
mb_internal_encoding('UTF-8');
$html = get_template_contents('invoice/pdf.php');
$data = generatePDFInvoice($html);

// Start downloading
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename="order_'.$get['1'].'.pdf"');
header('Content-Length: ' . strlen($data));

exit($data);

function generatePDFInvoice($html) {
	require SITE_ROOT . '/dompdf/vendor/autoload.php';
	$DOMPDF = new Dompdf();

	$DOMPDF->load_html($html);
	$DOMPDF->render();
	$result =  $DOMPDF->output();

	return $result;
}