<?php
if (!isset($_SESSION)) {
	session_start();
}
if (empty($_SESSION)) {
	syslog(LOG_INFO, __file__ . ': warning:empty SESSION'.serialize($_SESSION));
	$res = ['error' => 'no-session'];	
	echo json_encode($res);
	exit;
}
include_once('/usr/local/lib/jyctel/tools/mh/conf/config.php');
include_once(__dir__ . '/../config.php');

$_REQUEST = json_decode(file_get_contents("php://input"), TRUE);

includeModule('Resellers');

$reseller = new Reseller($_SESSION['reseller']);
$resellers = new Resellers($reseller);

if (isset($_REQUEST['get_balance_resellers'])) {
	
	$res = ['balance' => $resellers->actionBalance()];	
	echo json_encode($res);
	exit;
}
if (isset($_REQUEST['get_messages'])) {
	
	$res = [$_REQUEST['campo'] => $resellers->actionMessages()];	
	echo json_encode($res);
	exit;
}
/*$orders = [
	0 => ["i" => "1","id_order"=>"1113","fecha" => "01-02-2023","destinatario" => "caridad","telefono"=>"5300000000","monto" => "122$","productos"=>"","estado" => "enviado","pdf" => 1],
	1 => ["i" => "2","id_order"=>"1113","fecha" => "01-02-2023","destinatario" => "caridad","telefono"=>"5300000001","monto" => "52.4$","productos"=>"","estado" => "enviado","pdf" => 2]
];*/
if (isset($_REQUEST['get_orders_reseller'])) {

	$orders = $resellers->actionOrders();

	$res = [
		'orders' => $orders,
	    'info_labels' => [	
			'total' => count($orders), 
			'total_sales' => $resellers->getTotalSales($orders),
		],
		'filters' => $_REQUEST['filters'],
		'search' => (empty($_REQUEST['filters']) ? 'getBoughtProducts' : 'getCustomerOrders')
	];	

	echo json_encode($res);
}
exit;
