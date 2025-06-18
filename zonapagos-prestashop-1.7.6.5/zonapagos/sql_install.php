<?php
/*
* 2007-2016 PrestaShop
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
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$sql = array();
$sql[_DB_PREFIX_.'order_state'] = 'INSERT INTO `'._DB_PREFIX_.'order_state` (
			`id_order_state`, 
			`invoice`, 
			`send_email`, 
			`module_name`, 
			`color`, 
			`unremovable`, 
			`hidden`, 
			`logable`, 
			`delivery`, 
			`shipped`, 
			`paid`, 
			`pdf_invoice`, 
			`pdf_delivery`, 
			`deleted`) 
			VALUES (99, 0, 0, \'zonapagos\', \'#4169E1\', 1, 0, 0, 0, 0, 0, 0, 0, 0);';

$sql[_DB_PREFIX_.'order_state_lang1'] = 'INSERT INTO `'._DB_PREFIX_.'order_state_lang` (
			`id_order_state`, 
			`id_lang`, `name`, 
			`template`) 
			VALUES (99, 1, \'Pendiente\', \'zonapagos\');';

$sql[_DB_PREFIX_.'order_state_lang2'] = 'INSERT INTO `'._DB_PREFIX_.'order_state_lang` (
			`id_order_state`, 
			`id_lang`, `name`, 
			`template`) 
			VALUES (99, 2, \'Pendiente\', \'zonapagos\');';

$sql[_DB_PREFIX_.'zonapagos'] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'zonapagos` (
			  `order_id` int(10) NOT NULL,
			  `id_forma_pago` varchar(255) DEFAULT NULL,
			  `forma_pago` varchar(255) DEFAULT NULL,
			  `ticketID` varchar(255) DEFAULT NULL,
			  `codigo_servicio` varchar(255) DEFAULT NULL,
			  `codigo_banco` varchar(255) DEFAULT NULL,
			  `nombre_banco` varchar(255) DEFAULT NULL,
			  `codigo_transaccion` varchar(255) DEFAULT NULL,
			  `ciclo_transaccion` varchar(255) DEFAULT NULL,
			  `num_tarjeta` varchar(255) DEFAULT NULL,
			  `franquicia` varchar(255) DEFAULT NULL,
			  `cod_aprobacion` varchar(255) DEFAULT NULL,
			  `num_recibo` varchar(255) DEFAULT NULL,
			  `detalle_estado` varchar(255) DEFAULT NULL,
			  `ultima_consulta` varchar(255) DEFAULT NULL,
			  PRIMARY KEY (`order_id`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
