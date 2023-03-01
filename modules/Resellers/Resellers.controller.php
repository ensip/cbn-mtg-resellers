<?php

class Reseller {
	private $reseller = [];
	public function __construct($reseller) 
	{
		$this->reseller = $reseller;

	}
	public function getEmail() 
	{
		return $this->reseller['user'];
	}
	public function getId() 
	{
		return $this->reseller['id'];
	}
}
class Resellers {
	private $lang = 1;
	private $model = null;
	private $moneda = '$';
	private $reseller = null;
	public function __construct($reseller) 
	{
		$this->model = new ResellersModel($reseller, $this->lang);
		$this->reseller = $reseller;
	}
	public function actionBalance() 
	{
		return $this->model->getBalance();
	}
	public function actionMessages() 
	{
		$order_id = (new Request())->get('order_id');
		return $this->model->getMessages($order_id);
	}	
	public function actionOrders()
	{
		$this->model->setFilters();
		return $this->model->getOrders();
	}
	public function getTotalSales($orders) {
		if (empty($orders)) {
			return 0;
		}
		$monto = 0;
		foreach ($orders as $order) {
			$monto += $order['no-header']['monto'];
		}
		return $monto.$this->moneda;
	}
	public function orderStates() {
		return $this->model->getOrderStates();
	}
	public static function pdf() {
        $id_order = (new Request())->get('id_order_pdf');
		try {
			$ps = new PsBridge();
			$ctx = \Context::getContext();
			$order = new Order($id_order);
			$invoice = $order->getInvoicesCollection();
			$pdf = new PDF($invoice, PDF::TEMPLATE_INVOICE, $ctx->smarty);

			print_r($pdf->render('I',true)); //D descarga el fichero
		} catch (Exception $e) {
			print_r($e);
		}
	}
	public function provincias() {
		return $this->model->getStates();
	}
}
