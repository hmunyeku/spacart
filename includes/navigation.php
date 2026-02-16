<?php
if ($_POST['load_page']) {
    $page = $_POST['load_page'] + 1;
} elseif ($_GET['page'])
    $page = $_GET['page'];
else
    $page = 1;

$first_page = ($page - 1)* $objects_per_page;

$totalPages = ceil($total_items / $objects_per_page);
$template['totalPages'] = $totalPages;
$template['total_pages'] = $totalPages + 1;

$maxPagesToShow = 5;
$startPage = max(1, $page - floor($maxPagesToShow / 2));
$endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
// Adjust start page if we are near the end
if ($endPage - $startPage + 1 < $maxPagesToShow && $totalPages > $maxPagesToShow) {
    $startPage = $totalPages - $maxPagesToShow + 1;
}

$template['maxPagesToShow'] = $maxPagesToShow;
$template['startPage'] = $startPage;
$template['endPage'] = $endPage;
$template['currentPage'] = $page;

return;