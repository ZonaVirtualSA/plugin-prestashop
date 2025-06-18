<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');

$context = Context::getContext();

$order_state = Db::getInstance()->getValue('SELECT `id_order_state` FROM `'._DB_PREFIX_.'order_state` WHERE `module_name` = \'zonapagos\';');

$sql = 'SELECT id_order
	FROM ' . _DB_PREFIX_ . 'orders o
	WHERE o.`date_add` < DATE_SUB(NOW(),INTERVAL 7 MINUTE) AND 
        o.`module` = \'zonapagos\' AND o.`current_state` = ' . (int) $order_state . '
	' . Shop::addSqlRestriction(false, 'o') . '
	ORDER BY invoice_date ASC';
$registros = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
foreach ($registros as $pending_order) {
	$id_pago = (int) $pending_order['id_order'];
	$order = new Order($id_pago);
	
	$periodo_sonda = 600;
        $tipo_pago = Db::getInstance()->getValue('SELECT `id_forma_pago` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $order->id);
        $tiempo = 0;
        
	$history = new OrderHistory();
	$history->id_order = (int) $order->id;
	$history->id_employee = 1;

	if ($tipo_pago == "42")
            $periodo_sonda = 3600;
        $ultima_consulta = Db::getInstance()->getValue('SELECT `ultima_consulta` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $order->id);
        if ($ultima_consulta)
            $tiempo = $ultima_consulta;
        if (time() > $tiempo + $periodo_sonda) {
		$service_url = 'https://www.zonapagos.com/WsVerificarPagoV4/VerificarPagos.asmx?wsdl';
		$client = new SoapClient($service_url);
		$params = array(
		    'int_id_comercio' => Configuration::get('INT_ID_COMERCIO'),
		    'str_usr_comercio' => Configuration::get('STR_USR_COMERCIO'),
		    'str_pwd_Comercio' => Configuration::get('STR_PWD_COMERCIO'),
		    'str_id_pago' => $order->id,
		    'int_no_pago' => -1,
		    'int_error' => 0,
		    'int_cantidad_pagos' => 0
		);
		$result = $client->__soapCall('verificar_pago_v4', array($params));
		Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `ultima_consulta`= '".time()."' WHERE `order_id` = " . $order->id);
		if ($result->verificar_pago_v4Result == 1) {
		    $pagos = $result->int_cantidad_pagos;
		    $transacciones = explode("| ; |", $result->str_res_pago);
		    $transacciones_array = [];
		    foreach ($transacciones as $transaccion) {
			$transaccion_array = explode(" | ", $transaccion);
			if (sizeof($transaccion_array) > 1)
			    array_push($transacciones_array, $transaccion_array);
		    }

		    foreach ($transacciones_array as $transaccion) {
			$estado_pago = $transaccion[1];
			switch ($estado_pago) {
			    case "1":
				$history->changeIdOrderState(2, (int) ($order->id));
				$order->setCurrentState(2);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago exitoso' WHERE `order_id` = " . $order->id);
				break;
			    case "888":
				$history->changeIdOrderState($order_state, (int) ($order->id));
				$order->setCurrentState($order_state);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago pendiente por iniciar' WHERE `order_id` = " . $order->id);
				break;
			    case "999":
				$history->changeIdOrderState($order_state, (int) ($order->id));
				$order->setCurrentState($order_state);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago pendiente por finalizar' WHERE `order_id` = " . $order->id);
				break;
			    case "4001":
				$history->changeIdOrderState($order_state, (int) ($order->id));
				$order->setCurrentState($order_state);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago pendiente por CR' WHERE `order_id` = " . $order->id);
				break;
			    case "1000":
				$history->changeIdOrderState(6, (int) ($order->id));
				$order->setCurrentState(6);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago rechazado' WHERE `order_id` = " . $order->id);
				break;
			    case "1001":
				$history->changeIdOrderState(6, (int) ($order->id));
				$order->setCurrentState(6);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago rechazado. Error entre PSE y la Entidad Bancaria' WHERE `order_id` = " . $order->id);
				break;
			    case "4000":
				$history->changeIdOrderState(6, (int) ($order->id));
				$order->setCurrentState(6);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago rechazado CR' WHERE `order_id` = " . $order->id);
				break;
			    case "4003":
				$history->changeIdOrderState(6, (int) ($order->id));
				$order->setCurrentState(6);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `detalle_estado`= 'Pago rechazado. Error CR' WHERE `order_id` = " . $order->id);
				break;
			}
			$order->update();
			$forma_pago = $transaccion[14];
			switch ($forma_pago) {
			    case "29":
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `id_forma_pago`= '29' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `forma_pago`= 'PSE' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `ticketID`= '".$transaccion[15]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `codigo_servicio`= '".$transaccion[16]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `codigo_banco`= '".$transaccion[17]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `nombre_banco`= '".$transaccion[18]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `codigo_transaccion`= '".$transaccion[19]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `ciclo_transaccion`= '".$transaccion[20]."' WHERE `order_id` = " . $order->id);
				break;
			    case "32":
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `id_forma_pago`= '32' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `forma_pago`= 'Tarjeta de crédito' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `ticketID`= '".$transaccion[15]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `num_tarjeta`= '".$transaccion[16]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `franquicia`= '".$transaccion[17]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `cod_aprobacion`= '".$transaccion[18]."' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `num_recibo`= '".$transaccion[19]."' WHERE `order_id` = " . $order->id);
				update_post_meta($id_pago, 'id_forma_pago', '32');
				update_post_meta($id_pago, 'forma_pago', 'Tarjeta de crédito');
				update_post_meta($id_pago, 'ticketID', $transaccion[15]);
				update_post_meta($id_pago, 'num_tarjeta', $transaccion[16]);
				update_post_meta($id_pago, 'franquicia', $transaccion[17]);
				update_post_meta($id_pago, 'cod_aprobacion', $transaccion[18]);
				update_post_meta($id_pago, 'num_recibo', $transaccion[19]);
				break;
			    case "41":
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `id_forma_pago`= '41' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `forma_pago`= 'PDF generado en ZonaPAGOS' WHERE `order_id` = " . $order->id);
				break;
			    case "42":
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `id_forma_pago`= '42' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `forma_pago`= 'Gana' WHERE `order_id` = " . $order->id);
				break;
			    case "45":
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `id_forma_pago`= '45' WHERE `order_id` = " . $order->id);
				Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."zonapagos` SET `forma_pago`= 'Tarjeta Tuya' WHERE `order_id` = " . $order->id);
				break;
			}
		    }
		} else {
		    echo "Error:" . $result->str_detalle;
		}
	}
}
