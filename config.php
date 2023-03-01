<?php
class Filters {
	const FECHA_ORDENES = 'date_add';//antes date_add
}
class Storage {
	const ORDENES_BBDD_LACUBANACONECTA = 'ordenes_bbdd_lacubanaconecta';
	const PRODUCTOS_BBDD_LACUBANACONECTA = 'productos_bbdd_lacubanaconecta';
}
class ExportarTipoOrders{
	const ALMACEN = 'almacen';
	const CONTABILIDAD = 'contabilidad';
}

function includeBridgePs() {
	foreach (['./', '../', '../../', '../../../'] as $path) {
		if (is_file($path . 'conector_lacubana/PsBridge.class.php')) {
			include_once($path . 'conector_lacubana/PsBridge.class.php');
		}
	}
}

function includeClass($classes, $path = '') {
	foreach ($classes as $class) {
		$file = __DIR__.$path.'/clases/'.ucfirst($class).'.php';

		if (is_file($file)){
			include_once($file);
		}
	}
}

function includeFactoryDir() {
	$dirs = array("./clases/factories/","../clases/factories/","../../clases/factories/");
	foreach ($dirs as $dir) {
		foreach (glob($dir. "*.php") as $filename) {
			include_once $filename;
		}
	}
}
function includeModule($module) {
	$path_gestor = __DIR__ . '/modules/' . $module . '/';
	
	if (is_dir($path_gestor)) {
		include( $path_gestor . $module . ".model.php");
		include( $path_gestor . $module . ".controller.php");
	}
}

includeClass(['Helpers','Request'], '/../mtg');
