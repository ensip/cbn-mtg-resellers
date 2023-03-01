<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION)) {
	session_start();
}
include_once('/usr/local/lib/jyctel/tools/mh/conf/config.php');
//include_once( __dir__ . '/config.php');
include_once( __dir__ . '/config.php');

if ((isset($_SESSION) && !isset($_SESSION['reseller']['user']))) {
	echo "<script language='JavaScript'>location='login.php'</script>"; exit;	
}
include_once( PATH_MH . '/functions.php');
include_once( PATH_MH . '/sqlTablaProductos.php');
include_once( '../gestor/inc/gestClases.Class.php');
include_once( '../gestor/inc/Forms.Class.php');
include_once( '../gestor/inc/Productos.Class.php');

includeModule('Resellers');
$reseller = new Reseller($_SESSION['reseller']);
$resellers = new Resellers($reseller);
$provincias = $resellers->provincias();
$estados_orden = $resellers->orderStates();

/*Para ver las ordenes por pantalla sin ajax, poner ?no_ajax en la url y activar los filtros que se quieran*/
if (isset($_GET['no_ajax'])) {
	//$_REQUEST['filters'] =  ['order_state' => 16];
	//$_REQUEST['filters'] =  ['id_order' => 10353, 'hay_filtros' => true];
	//$_REQUEST['filters'] =  ['order_state' => 'ENVIADO', 'hay_filtros' => true];
	//$_REQUEST['filters'] =  ['fecha_ini' => date('2023-02-01'), 'hay_filtros' => true];
	$o = $resellers->actionOrders();
	//$ts = $resellers->getTotalSales($o);
	debug($o);
}

if (isset($_GET['pdf'])) {
	Resellers::pdf(); //printa el pdf por pantalla en ventana nueva
}
?>
<!doctype html>
<html>
<head lang="es">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="stylesheet" type="text/css" href="../gestor/css/bootstrap-datetimepicker.css">
	<link rel="stylesheet" type="text/css" href="./assets/css/style.css?v=<?=date('ihs')?>">
	<title>Resellers cbn - Ordenes</title>  
</head>
<body>
	<nav class="navbar navbar-expand-md bg-info text-light div_menu">
		<div class="collapse navbar-collapse">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item">
					<a href="./index.php" class="nav-link text-light h6 m-0">Ordenes</a>
				</li>
			</ul>
		</div>
		<div><?=$reseller->getEmail();?> - <a href="./login.php?logout=1" class="text-light">Desconectar</a></div>
	</nav>
	<div class="container">
		<div class="d-flex flex-wrap cards-orders justify-content-between">
			<div class="col-lg-4 p-0 mt-1">
				<form action="" method="POST" style="display:block;" id="form-filter-orders">
					<div class="card-group card-search p-1">
					<div class="card p-1 border-right-0">
						<div class="input-group input-group-sm">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">Fecha inicial</span>
							</div>
							<input type="text" class="datetimepicker_ini form-control" attr-filter="1" value="" name="fecha_ini" title="Fecha inicial creación orden" placeholder="dd/mm/yy">
						</div>
					<div class="input-group input-group-sm">
							<select name="order_state" class="custom-select" title="Eliminará filtro de orden">
								<option value="-">Estado orden</option>
<?php	foreach ($estados_orden as $key => $value) { ?>
								<option value="<?=$key?>" ><?=$value?></option>
<?php } ?>
							</select>
							</div>
<?php /* ?>
							<div class="input-group input-group-sm">
								<select name="name_state" class="custom-select" title="Eliminará filtro de orden">
									<option value="-">Escoge Provincia</option>
<?php	foreach ($provincias as $key => $value) { ?>
									<option value="<?=$value['id__state']?>" ><?=$value['name']?></option>
<?php } ?>
								</select>
							</div>
<?php */ ?>
						</div>
						<div class="card p-1">
							<div class="input-group input-group-sm">
								<div class="input-group-prepend">
									<span class="input-group-text" id="basic-addon1">Fecha final</span>
								</div>
								<input type="text" class="datetimepicker_fin form-control" attr-filter="1" value="" name="fecha_fin" title="Fecha final orden creada" placeholder="dd/mm/yy">
							</div>
							<div class="input-group input-group-sm">
								<input type="text" value="" name="id_order" placeholder="Id Orden" class="form-control form-sm" title="Eliminará el filtro de fechas">
							</div>
							<div class="input-group input-group-sm">
								<input type="button" name="filter" value="Buscar" class="btn btn-sm btn-info w-100">
							</div>
						</div>
					</div>
					</form>
				</div>

				<div class="flex-column col-md-2 p-0">
					<div class="card m-2 card-info" >
						<div class="card-header bg-white border-bottom-0 p-0 text-secondary">
							<div class="text-info w-100 p-2 text-center" id="loading-btn"></div>						
						</div>
						<div class="card-body p-0" id="card-body-info"></div>
					</div>
				</div>
		</div>
		<div class="d-flex flex-wrap">
				<div class="col col-lg-2 m-1 p-0">
					<div class="card p-2">
						<div class="input-group input-group-sm justify-content-around">
							<input type="button" name="export-orders" value="Exportar para conciliación" class="btn btn-sm btn-info btn-exportar m-sm-1">
							<div class="ml-2 pt-2 text-info" id="loading-csv"></div>
							<div class="export-csv ml-2 pt-2"></div>
						</div>
					</div>
				</div>
		</div>
		<hr class="p-0 m-1">
		<div class="d-flex flex-wrap">
			<div id="filters-info" class="p-1 form-inline"></div>
		</div>
		<div class="d-flex flex-wrap container-bg bg-white">
			<div class="col-lg-12 p-2 text-info" id="loading-orders"></div>
			<div class="col-lg-12 p-0" id="content-orders"></div>
		</div>
		<div id="container-extra">
		</div>	
	</div>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" ></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script src="../gestor/js/moment.min.js" ></script>
	<script src="../gestor/js/bootstrap-datetimepicker.js" ></script>
    <script src="./assets/js/axios.min.js"></script>

	<link rel="preload" as="script" href="./assets/js/resellers.js?v=<?=date('yihs')?>" crossorigin="">
	<script type="module">
		import { resellers } from './assets/js/resellers.js?v=<?=date('yihs')?>';
		resellers.init({
		    'datetimepicker' :['.datetimepicker_ini', '.datetimepicker_fin'],
		})
	</script>
</body>
</html>
