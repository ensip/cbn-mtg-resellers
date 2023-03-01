<?php
includeBridgePs();

class ResellersModel {

	private $filters = [];
	private $id_country = 75;
	private $lang = 0;
	private $reseller = null;

	public function __construct($reseller, $lang) 
	{
		$this->reseller = $reseller;
		$this->lang = $lang;
		$ps = new PsBridge();

	}
	private function format_order($val) 
	{
		if (empty($val->id)) {
			return false;
		}
		$list_order = [];
		$list_order['id_order'] = $val->id;
		$list_order['id_currency'] = $val->id_currency;
		$list_order['id_address_delivery'] = $val->id_address_delivery;
		$list_order['date_add'] = $val->date_add;
		$list_order['external_reference'] = $val->external_reference;
		$list_order['total_paid'] = $val->total_paid;
		$list_order['current_state'] = $val->current_state;

		return $list_order;
	}
	//0 => ["i" => "1","id_order"=>"1113","fecha" => "01-02-2023","monto"=>"123$","destinatario" => "caridad","telefono"=>"telf1","total" => "1223","productos"=>"","estado" => "enviado","pdf" => 1],
	private function format_orders($val, $index)
	{
		$list_order = [];
		//$order = new Order($val['id_order'], $val['id_lang']);
		$moneda = ($val['id_currency'] == 2 ? '$' : '€');
		$address = new Address($val['id_address_delivery']);
		$list_order['id'] = $index + 1;
		$list_order['id_order'] = $val['id_order'];
		$list_order['external_reference'] = $val['external_reference'];	
		$list_order['fecha'] = $val['date_add'];
		$order = new Order($val['id_order']);
		$arr_history = $order->getHistory(1, 5);
		list($history) = $arr_history;
		$list_order['fecha_entregado'] = (!empty($history) ? $history['date_add'] : '-');
		$list_order['productos'] = array_map(array($this, 'format_product_list'), OrderDetail::getList($val['id_order']));
		$list_order['destino'] = State::getNameById($address->id_state);
		$list_order['destinatario'] = sprintf('%s %s', $address->firstname, $address->lastname);
		$list_order['telefono'] = $address->phone;
		$list_order['monto'] = sprintf('%s%s', str_replace('.', ',', str_replace(',', '', number_format($val['total_paid'], 3))), $moneda);
		$list_order['estado'] = '';

		if (isset($val['order_state'])) { 
			$list_order['estado'] = $val['order_state'];
		} else {
			$order_state = OrderState::getOrderStateByIdOrder($val['current_state'], $this->lang);
			list($os) = $order_state;
			$list_order['estado'] = $os['name'];
			$val['order_state_color'] = $os['color'];
		}
		//$list_order['productos'] = OrderDetail::getList($val['id_order']);
		$list_order['mensajes'] = $val['id_order'];	
		$list_order['pdf'] = $val['id_order'];	
		$list_order['no-header'] = [
			'current_state' => $val['current_state'], 
			'color' => $val['order_state_color'],
			'monto' => (float)number_format($val['total_paid'], 3),
			'cant_messages' => count($this->getMessages($val['id_order']))
		];

		return $list_order;
	}
	/*
	 *	['.$product['product_id'] . '] - '.$product['product_name']. ', '.$product['product_quantity']
	 *
	 * */
	private function format_product_list($product) {
		return '['.$product['product_id'] . '] - '.$product['product_name'];
	}
	public function getBalance()
	{
		$balance = ResellersMtg::getBalance($this->reseller->getId());
		//debug('buscar balance de ' .$this->reseller->getId() . '-'.$balance);

		return $balance;
	}	

	/*
	 *  params: string $key
	 *	@return value of filter's key pair
	 * */
	private function getValueFilter($key) 
	{
		return $this->filters[$key];
	}

	/*
	 *  params: string $key
	 *	@return true/false if key is a filter
	 * */
	private function ifFilter($key) 
	{
		return (isset($this->filters[$key]) ? true : false);
	}
	public function getMessages($order_id) {
		
		$customer_id = $this->reseller->getId();
		$messages = CustomerThread::getCustomerMessages($customer_id, null, $order_id);
		return $messages;
	}
	public function getOrders() 
	{
		$ids_order_state = [];
		$list_orders = [];
		$orders = [];
		$customer_id = $this->reseller->getId();
		
		if (!$this->hayFilters()) {
			syslog(LOG_INFO, __method__ . ':!hayFilters: getBoughtProducts');

			$customer = new Customer($customer_id);
			$orders = $customer->getBoughtProducts('', 'order by date_add desc'); //busqueda orders
		} else {
			$id_order = ($this->ifFilter('id_order') ? $this->getValueFilter('id_order') : null);
			$order_state = ($this->ifFilter('order_state') ? $this->getValueFilter('order_state') : null);
			switch ($order_state) {
				case 'CANCELADO':
				case 'EN TRAMITE':
					$map = ResellersModel::StatesMap();
					$ids_state = [];
					foreach ($map as $ioe => $state) {
						if ($state == $order_state) {
							$ids_order_state[] = $ioe;
						}
					}

					break;
				case 'ENTREGADO':
					$ids_order_state[] = 5;
					break;
			}
			$fecha_ini = ($this->ifFilter('fecha_ini') ? $this->getValueFilter('fecha_ini') . ' 00:00:00' : null);
			$fecha_fin = ($this->ifFilter('fecha_fin') ? $this->getValueFilter('fecha_fin') . ' h:i:s' : null);

			if ($fecha_ini && is_null($fecha_fin)) {
				$fecha_fin = date('Y-m-d H:i:s');
			}
			if ($fecha_fin && is_null($fecha_ini)) {
				$fecha_ini = date('Y-m-d H:i:s');
			}
			if (isset($_GET['no_ajax'])) {
				debug($this->filters, 'Fechas: '.$fecha_ini . ' - ' .$fecha_fin);
			}
			$ids_order_to_map = [];
			
			//SI NO HAY ORDEN Y HAY FECHAS
			if (is_null($id_order) && !is_null($fecha_ini) && !is_null($fecha_fin)) {
				/* get orders with one day interval ( -1, day) */
				$fecha_ini = date('Y-m-d 00:00:00', strtotime($fecha_ini . ' +2 day'));
				$ids_orders = Order::getOrdersIdByDate($fecha_ini, $fecha_fin, $customer_id);
				syslog(LOG_INFO, __method__ . ":hayFilters: getOrdersIdByDate - null => id-order, hay fechas: ".$fecha_ini." . ' - ' .$fecha_fin");
				
				$orders_to_map = [];
				foreach ($ids_orders as $id_order) {
					$continue = true;
					$order_to_map = new Order($id_order); //order by id
					if (!empty($ids_order_state)) {
						if (!in_array($order_to_map->current_state, $ids_order_state)) {
							$continue = false;
						}
					}
					if ($continue) {
						$ids_orders_to_map[] = $id_order;
						$orders_to_map[] = $order_to_map;
					}
				}
				if (!empty($orders_to_map)) {
					$orders_unsorted = array_map(array($this, 'format_order'), $orders_to_map, $ids_orders_to_map);
					$orders = Helpers::array_sort($orders_unsorted, 'date_add', SORT_DESC);
				}

			} else {
				$order = new Order($id_order);//order by id

				if (!$this->hayOrden($id_order)) {
					syslog(LOG_INFO, __method__ . ':!hayOrden: getCustomerOrders');
					
					$orders = $order->getCustomerOrders($customer_id, false, null, 0, 200); //order customer OK, funciona
					if (!empty($ids_order_state)) {
						$orders = array_values(array_filter($orders, function($o) use ($ids_order_state) {
							return (in_array($o['current_state'], $ids_order_state)); 
						}));
					}	
				} else {
					syslog(LOG_INFO, __method__ . ":hayOrden: customer_id: $customer_id");
					if ((int)$order->id_customer == (int)$customer_id) {
						$orders_to_map[] = $order;
						$orders = array_map(array($this, 'format_order'), $orders_to_map, array_keys($orders_to_map));
					}
				}
			}
		}
		$list_orders = array_map(array($this, 'format_orders'), $orders, array_keys($orders));
	
		return $list_orders;
	}
	public function getOrderStates() 
	{
		$estados_orden = OrderState::getOrderStates($this->lang);
		foreach ($estados_orden as $e) {
			
			$estados[ResellersModel::StatesMap($e['id_order_state'])] = ResellersModel::StatesMap($e['id_order_state']);

		}
		return $estados;
	}
	public function getStates() 
	{
		$st = function ($e) {
			return [
				'name' => $e['name'],
				'id_state' => $e['id_state']
			];
		};

		$sql = 'SELECT  a.*
		, z.`name` AS zone, cl.`name` AS country
		FROM `ps_state` a 
		LEFT JOIN `ps_zone` z ON (z.`id_zone` = a.`id_zone`)
				LEFT JOIN `ps_country_lang` cl ON (cl.`id_country` = a.`id_country` AND cl.id_lang = '.$this->lang.') 
				 WHERE 1  AND `iso_code` LIKE \'%-r%\'  AND cl.`id_country` = '.$this->id_country.' 
 		ORDER BY a.iso_code ASC';
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

		return array_map($st, $result);
	}
	private function hayFilters() 
	{
		return (empty($this->filters) ? false : true);
	}
	private function hayOrden($id) 
	{
		return (is_null($id) ? false : true);
	}
	public static function StatesMap($val = null) {
		$map = array(
			1 => 'EN TRAMITE',
			2 => 'EN TRAMITE',
			3 => 'EN TRAMITE',
			4 => 'EN TRAMITE',
			5 => 'ENTREGADO',
			6 => 'CANCELADO',
			7 => 'EN TRAMITE',
			8 => 'EN TRAMITE',
			9 => 'EN TRAMITE',
			10 => 'EN TRAMITE',
			11 => 'EN TRAMITE',
			12 => 'EN TRAMITE',
			13 => 'EN TRAMITE',
			14 => 'EN TRAMITE',
			15 => 'EN TRAMITE',
			16 => 'EN TRAMITE',
			17 => 'EN TRAMITE',
			18 => 'EN TRAMITE',
			19 => 'EN TRAMITE',
			20 => 'EN TRAMITE',
			21 => 'CANCELADO'
		);
		return (!is_null($val) && isset($map[$val]) ? $map[$val] : $map);
	}
	public function setFilters() 
	{
		if (isset($_REQUEST['filters']['hay_filtros']) && $_REQUEST['filters']['hay_filtros']) {
			$this->filters = $_REQUEST['filters'];
		}
	}
}
