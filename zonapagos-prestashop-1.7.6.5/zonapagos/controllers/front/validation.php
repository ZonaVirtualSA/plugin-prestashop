<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class ZonapagosValidationModuleFrontController extends ModuleFrontController
{
    const ZONAPAGOS_VERIFICAR_LIVE = 'https://www.zonapagos.com/WsVerificarPagoV4/VerificarPagos.asmx?wsdl';
    const ZONAPAGOS_INICIAR_LIVE = 'https://www.zonapagos.com/ws_inicio_pagov2/Zpagos.asmx?wsdl';
        
    public function postProcess()
    {
    	$cart = $this->context->cart;
    	$currency = $this->context->currency;
    	$customer = new Customer($cart->id_customer);
    	$total = (float) $cart->getOrderTotal(true, Cart::BOTH);
    	$order_state = Db::getInstance()->getValue('SELECT `id_order_state` FROM `'._DB_PREFIX_.'order_state` WHERE `module_name` = \'zonapagos\';');
    	
        $sql = 'SELECT id_order
		FROM ' . _DB_PREFIX_ . 'orders o
		WHERE o.`module` = \'zonapagos\' AND 
                o.`id_customer` = ' . $customer->id . ' AND 
                o.`current_state` = ' . (int) $order_state . '
		' . Shop::addSqlRestriction(false, 'o') . '
		ORDER BY id_order ASC LIMIT 1';
	$pedido_pendientes = Db::getInstance()->ExecuteS($sql);
	if(sizeof($pedido_pendientes) === 0) {
		$order_id = 1 + (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT MAX(id_order) FROM `'._DB_PREFIX_.'orders`');
                //$pago_ok = $this->pago_ok($order_id);
                //if ($pago_ok) {
                    $service_url = self::ZONAPAGOS_INICIAR_LIVE;

                    $client = new SoapClient($service_url);
                    $ZonaPAGOS_args = $this->get_zonapagos_args($order_id);
                    $result = $client->__soapCall('inicio_pagoV2', array($ZonaPAGOS_args));
                    $identificador = $result->inicio_pagoV2Result;

                    $redirectUrl = 'https://www.zonapagos.com/' . Configuration::get('T_RUTA') . '/pago.asp?estado_pago=iniciar_pago&identificador=' . $identificador;
                    $this->module->validateOrder((int) $cart->id, $order_state, $total, $this->module->displayName, NULL, NULL, (int) $currency->id, false, $customer->secure_key);
                    Db::getInstance()->execute("INSERT INTO `"._DB_PREFIX_."zonapagos` (`order_id`, `id_forma_pago`, `forma_pago`, `ticketID`, `codigo_servicio`, `codigo_banco`, `nombre_banco`, `codigo_transaccion`, `ciclo_transaccion`, `num_tarjeta`, `franquicia`, `cod_aprobacion`, `num_recibo`, `detalle_estado`, `ultima_consulta`) VALUES (".$order_id.", NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);");
                    Tools::redirect($redirectUrl);
					/*
                } else {
                    $cus = $this->cus($order_id);
                    if(sizeof($cus) == 0){
                        $message = 'En este momento su Número de Referencia o Factura (' . $order_id . ') presenta un proceso de pago cuya transacción se encuentra PENDIENTE de recibir confirmación por parte de su entidadfinanciera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente ' . Configuration::get('PHONE') . ' o enviar un correo electrónico a ' . Configuration::get('EMAIL') . '.';
                    } elseif($cus[0] == "32"){
                        $message = 'En este momento su Número de Referencia o Factura (' . $order_id . ') presenta un proceso de pago cuya transacción se encuentra PENDIENTE de recibir confirmación por parte de su entidadfinanciera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente ' . Configuration::get('PHONE') . ' o enviar un correo electrónico a ' . Configuration::get('EMAIL') . '.';
                    } elseif($cus[0] == "29"){
                        $message = 'En este momento su Número de Referencia o Factura (' . $order_id . ') presenta un proceso de pago cuya transacción se encuentra PENDIENTE de recibir confirmación por parte de su entidad financiera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente ' . Configuration::get('PHONE') . ' o enviar un correo electrónico a ' . Configuration::get('EMAIL') . ' y preguntar por el estado de la transacción: ' . $cus[1] . '.';
                    }
                    Tools::redirect(Context::getContext()->link->getModuleLink('zonapagos','payment', array('message' => $message)));
                }*/
            } else {
                $cus = $this->cus($pedido_pendientes[0]['id_order']);
                if(sizeof($cus) == 0){
                    $message = 'En este momento su Número de Referencia o Factura (' . $pedido_pendientes[0]['id_order'] . ') presenta un proceso de pago cuya transacción se encuentra PENDIENTE de recibir confirmación por parte de su entidadfinanciera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente ' . Configuration::get('PHONE') . ' o enviar un correo electrónico a ' . Configuration::get('EMAIL') . '.';
                } elseif($cus[0] == "32"){
                    $message = 'En este momento su Número de Referencia o Factura (' . $pedido_pendientes[0]['id_order'] . ') presenta un proceso de pago cuya transacción se encuentra PENDIENTE de recibir confirmación por parte de su entidadfinanciera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente ' . Configuration::get('PHONE') . ' o enviar un correo electrónico a ' . Configuration::get('EMAIL') . '.';
                } elseif($cus[0] == "29"){
                    $message = 'En este momento su Número de Referencia o Factura (' . $pedido_pendientes[0]['id_order'] . ') presenta un proceso de pago cuya transacción se encuentra PENDIENTE de recibir confirmación por parte de su entidad financiera, por favor espere unos minutos y vuelva a consultar más tarde para verificar si su pago fue confirmado de forma exitosa. Si desea mayor información sobre el estado actual de su operación puede comunicarse a nuestras líneas de atención al cliente ' . Configuration::get('PHONE') . ' o enviar un correo electrónico a ' . Configuration::get('EMAIL') . ' y preguntar por el estado de la transacción: ' . $cus[1] . '.';
                }
                Tools::redirect(Context::getContext()->link->getModuleLink('zonapagos','payment', array('message' => $message)));
            }
    }
    
    function pago_ok($order_id) {
            $service_url = self::ZONAPAGOS_VERIFICAR_LIVE;
            $client = new SoapClient($service_url);

            $params = array(
                'int_id_comercio' => Configuration::get('INT_ID_COMERCIO'),
                'str_usr_comercio' => Configuration::get('STR_USR_COMERCIO'),
                'str_pwd_Comercio' => Configuration::get('STR_PWD_COMERCIO'),
                'str_id_pago' => $order_id,
                'int_no_pago' => -1,
                'int_error' => 0,
                'int_cantidad_pagos' => 0
            );
            $result = $client->__soapCall('verificar_pago_v4', array($params));
            $response = true;
            if ($result->verificar_pago_v4Result == 1) {
                $pagos = $result->int_cantidad_pagos;
                if ($pagos > 0) {
                    $response = false;
                }
            } else {
                $response = false;
            }
            return $response;
        }
    
    function get_zonapagos_args($order_id) {
            //Zonapagos Args
            $cart = $this->context->cart;
    	    $customer = new Customer($cart->id_customer);
            $address = new AddressCore($cart->id_address_delivery);
            $zonapagos_args = array(
                'id_tienda' => Configuration::get('INT_ID_COMERCIO'),
                'clave' => Configuration::get('CLAVE'),
                'total_con_iva' => (float) $cart->getOrderTotal(true, Cart::BOTH),
                'valor_iva' => (float) $cart->getOrderTotal(true, Cart::BOTH) - (float) $cart->getOrderTotal(false, Cart::BOTH),
                'id_pago' => $order_id,
                'descripcion_pago' => 'Pago orden No. ' . $order_id,
                // informacion del cliente
                'email' => $customer->email,
                'id_cliente' => $customer->id,
                'tipo_id' => '7',
                'nombre_cliente' => $address->firstname,
                'apellido_cliente' => $address->lastname,
                'telefono_cliente' => $address->phone,
                'info_opcional1' => '',
                'info_opcional2' => '',
                'info_opcional3' => '',
                // tipo servicio en la pasarela
                'codigo_servicio_principal' => (string) Configuration::get('COD_SERVICIO'),
                'lista_codigos_servicio_multicredito' => null,
                'lista_nit_codigos_servicio_multicredito' => null,
                'lista_valores_con_iva' => null,
                'lista_valores_iva' => null,
                'total_codigos_servicio' => '0'
            );

            return $zonapagos_args;
        }
        
    function cus($order_id) {
            $service_url = self::ZONAPAGOS_VERIFICAR_LIVE;
            $client = new SoapClient($service_url);

            $params = array(
                'int_id_comercio' => Configuration::get('INT_ID_COMERCIO'),
                'str_usr_comercio' => Configuration::get('STR_USR_COMERCIO'),
                'str_pwd_Comercio' => Configuration::get('STR_PWD_COMERCIO'),
                'str_id_pago' => $order_id,
                'int_no_pago' => -1,
                'int_error' => 0,
                'int_cantidad_pagos' => 0
            );
            $result = $client->__soapCall('verificar_pago_v4', array($params));
            $cus = [];
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
                    $forma_pago = $transaccion[14];
                    if($estado_pago == "999" && $forma_pago == "29") {
                        $cus[0] = $forma_pago;
                    	$cus[1] = $transaccion[19];
                    }
                    if($estado_pago == "4001" && $forma_pago == "32") {
                        $cus[0] = $forma_pago;
                    }
                }
            }
            return $cus;
        }
}
