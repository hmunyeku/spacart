<?php
$template['location'] .= ' &gt; '.lng('Reviews');
if ($_POST['mode'] == "search") {
	$_SESSION['search_reviews'] = $_POST;
	redirect("/admin/reviews?mode=search");
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	extract($_POST, EXTR_SKIP);
	if ($mode == "delete" && !empty($to_delete)) {
		foreach($to_delete as $k=>$v) {
			$db->query("DELETE FROM reviews WHERE id='$k'");
		}

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> lng('Selected reviews deleted.')
		);
	} elseif ($mode == "update") {
		foreach($to_update as $k=>$v) {
			$db->array2update("reviews", $v, "id='$k'");
		}

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> lng('Reviews updated.')
		);
	}

	redirect("/admin/reviews?mode=search");
}

extract($_GET, EXTR_SKIP);
if ($mode == "search") {
	$data = $_SESSION['search_reviews'];
	$condition = array();
	$data = array_map('addslashes', $data);
	if ($data['status'] || $data['status'] == '0')
		$condition[] = "reviews.status='$data[status]'";

	if ($data['rating'])
		$condition[] = "reviews.rating='$data[rating]'";

	if ($data['productid'])
		$condition[] = "reviews.productid='".$data['productid']."'";

	if ($data['sku'])
		$condition[] = "products.sku='".$data['sku']."'";

	if ($data['remote_ip'])
		$condition[] = "reviews.remote_ip='".$data['remote_ip']."'";

	if ($data['name'])
		$condition[] = "reviews.name LIKE '%".$data['name']."%'";

	if ($data['message'])
		$condition[] = "reviews.message LIKE '%".$data['message']."%'";

	$search_condition = '';
	if (!empty($condition))
		$search_condition = " WHERE ".implode(" AND ", $condition);

	$total_items = $db->field("SELECT COUNT(*) as cnt FROM reviews LEFT JOIN products ON products.productid=reviews.productid".$search_condition);
	if ($total_items) {
		$objects_per_page = 10;
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location.'/admin/reviews/?mode=search&';
		$reviews = $db->all("SELECT reviews.*, products.sku FROM reviews LEFT JOIN products ON products.productid=reviews.productid".$search_condition." LIMIT $first_page, $objects_per_page");

		$template['reviews'] = $reviews;
	}
}

$template['search_data'] = $_SESSION['search_reviews'];
$template['head_title'] = lng('Reviews').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/reviews.php');

$template['css'][] = 'admin_blog';
$template['js'][] = 'admin_blog';