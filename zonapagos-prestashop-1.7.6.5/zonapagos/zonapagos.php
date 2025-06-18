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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Zonapagos extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();

    public $t_ruta;
    public $cod_servicio;
    public $int_id_comercio;
    public $clave;
    public $str_usr_comercio;
    public $str_pwd_Comercio;
    public $email;
    public $phone;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'zonapagos';
        $this->tab = 'payments_gateways';
        $this->version = '1.7.6.5';
        $this->author = 'ZonaVirtual';
        $this->controllers = array('payment', 'validation');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('T_RUTA', 'COD_SERVICIO', 'INT_ID_COMERCIO', 'CLAVE', 'STR_USR_COMERCIO', 'STR_PWD_COMERCIO', 'EMAIL', 'PHONE'));
        if (isset($config['T_RUTA'])) {
            $this->t_ruta = $config['T_RUTA'];
        }
        if (isset($config['COD_SERVICIO'])) {
            $this->cod_servicio = $config['COD_SERVICIO'];
        }
	if (isset($config['INT_ID_COMERCIO'])) {
            $this->int_id_comercio = $config['INT_ID_COMERCIO'];
        }
	if (isset($config['CLAVE'])) {
            $this->clave = $config['CLAVE'];
        }
        if (isset($config['STR_USR_COMERCIO'])) {
            $this->str_usr_comercio = $config['STR_USR_COMERCIO'];
        }
	if (isset($config['STR_PWD_COMERCIO'])) {
            $this->str_pwd_Comercio = $config['STR_PWD_COMERCIO'];
        }
	if (isset($config['EMAIL'])) {
            $this->email = $config['EMAIL'];
        }
	if (isset($config['PHONE'])) {
            $this->phone = $config['PHONE'];
        }
	
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Payments by ZonaPAGOS');
        $this->description = $this->l('This module allows you to accept payments by ZonaPAGOS.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);

        if ((!isset($this->t_ruta) || !isset($this->cod_servicio) || !isset($this->int_id_comercio) || !isset($this->clave) || !isset($this->str_usr_comercio) || !isset($this->str_pwd_Comercio) || !isset($this->email) || !isset($this->phone) || empty($this->t_ruta) || empty($this->cod_servicio) || empty($this->int_id_comercio) || empty($this->clave) || empty($this->str_usr_comercio) || empty($this->str_pwd_Comercio) || empty($this->email) || empty($this->phone))) {
            $this->warning = $this->l('The "Path", "Service code", "Commerce Id", "Key", "Commerce user", "Commerce password", "Email" and "Phone" fields must be configured before using this module.');
        }
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('displayOrderDetail')
            && $this->installDb()
        ;
    }
    
    public function installDb()
    {
        $return = true;
        include(dirname(__FILE__).'/sql_install.php');
        foreach ($sql as $s) {
            $return &= Db::getInstance()->execute($s);
       	}
     	return $return;
    }

    public function uninstall()
    {
        return Configuration::deleteByName('T_RUTA')
            && Configuration::deleteByName('COD_SERVICIO')
            && Configuration::deleteByName('INT_ID_COMERCIO')
            && Configuration::deleteByName('CLAVE')
            && Configuration::deleteByName('STR_USR_COMERCIO')
            && Configuration::deleteByName('STR_PWD_COMERCIO')
            && Configuration::deleteByName('EMAIL')
            && Configuration::deleteByName('PHONE')
            && $this->uninstallDb()
            && parent::uninstall()
        ;
    }
    
    public function uninstallDb()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'zonapagos');
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'order_state_lang` WHERE `'._DB_PREFIX_.'order_state_lang`.`id_order_state` = 99;');
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'order_state` WHERE `'._DB_PREFIX_.'order_state`.`id_order_state` = 99;');
        return true;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('T_RUTA')) {
                $this->_postErrors[] = $this->l('The "Path" field is required.', array(),'Modules.zonapagos');
            } elseif (!Tools::getValue('COD_SERVICIO')) {
                $this->_postErrors[] = $this->l('The "Service code" field is required.');
            } elseif (!Tools::getValue('INT_ID_COMERCIO')) {
                $this->_postErrors[] = $this->l('The "Commerce Id" field is required.');
            } elseif (!Tools::getValue('CLAVE')) {
                $this->_postErrors[] = $this->l('The "Key" field is required.');
            } elseif (!Tools::getValue('STR_USR_COMERCIO')) {
                $this->_postErrors[] = $this->l('The "Commerce user" field is required.');
            } elseif (!Tools::getValue('STR_PWD_COMERCIO')) {
                $this->_postErrors[] = $this->l('The "Commerce password" field is required.');
            } elseif (!Tools::getValue('EMAIL')) {
                $this->_postErrors[] = $this->l('The "Email" field is required.');
            } elseif (!Tools::getValue('PHONE')) {
                $this->_postErrors[] = $this->l('The "Phone" field is required.');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('T_RUTA', Tools::getValue('T_RUTA'));
            Configuration::updateValue('COD_SERVICIO', Tools::getValue('COD_SERVICIO'));
            Configuration::updateValue('INT_ID_COMERCIO', Tools::getValue('INT_ID_COMERCIO'));
            Configuration::updateValue('CLAVE', Tools::getValue('CLAVE'));
            Configuration::updateValue('STR_USR_COMERCIO', Tools::getValue('STR_USR_COMERCIO'));
            Configuration::updateValue('STR_PWD_COMERCIO', Tools::getValue('STR_PWD_COMERCIO'));
            Configuration::updateValue('EMAIL', Tools::getValue('EMAIL'));
            Configuration::updateValue('PHONE', Tools::getValue('PHONE'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    private function _displayZonaPAGOS()
    {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }

    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->_displayZonaPAGOS();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
                ->setCallToActionText($this->l('Pay by ZonaPAGOS'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setAdditionalInformation($this->fetch('module:zonapagos/views/templates/front/payment_infos.tpl'));

        return [$newOption];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['order']->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_CHEQUE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')))) {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'shop_name' => $this->context->shop->name,
                'checkName' => $this->checkName,
                'checkAddress' => Tools::nl2br($this->address),
                'status' => 'ok',
                'id_order' => $params['order']->id
            ));
            if (isset($params['order']->reference) && !empty($params['order']->reference)) {
                $this->smarty->assign('reference', $params['order']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }
        return $this->fetch('module:zonapagos/views/templates/hook/payment_return.tpl');
    }
    
    public function hookDisplayOrderDetail($params)
    {
        if($params['order']->module == 'zonapagos'){
    		$id_forma_pago = Db::getInstance()->getValue('SELECT `id_forma_pago` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		$forma_pago = Db::getInstance()->getValue('SELECT `forma_pago` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		$detalle_estado = Db::getInstance()->getValue('SELECT `detalle_estado` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		$nombre_banco = Db::getInstance()->getValue('SELECT `nombre_banco` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		$codigo_transaccion = Db::getInstance()->getValue('SELECT `codigo_transaccion` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		$franquicia = Db::getInstance()->getValue('SELECT `franquicia` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		$num_tarjeta = Db::getInstance()->getValue('SELECT `num_tarjeta` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		$num_recibo = Db::getInstance()->getValue('SELECT `num_recibo` FROM `'._DB_PREFIX_.'zonapagos` WHERE `order_id` = ' . $params['order']->id);
    		if($id_forma_pago == "29"){
    			$params['order']->payment = $params['order']->payment . 
    				" - Forma de pago: " . $forma_pago .
    				" - Estado en curso del pago: " . $detalle_estado . 
    				" - Banco: " . $nombre_banco .
    				" - Código único de seguimiento de la transacción en PSE (CUS): " . $codigo_transaccion;
    		} elseif ($id_forma_pago == "32"){
    			$params['order']->payment = $params['order']->payment . 
    				" - Forma de pago: " . $forma_pago .
    				" - Estado en curso del pago: " . $detalle_estado . 
    				" - Franquicia: " . $franquicia .
    				" - Número de tarjeta: " . $num_tarjeta . 
    				" - Código de recibo de la transacción: " . $num_recibo;
    		} else {
    			$params['order']->payment = $params['order']->payment . 
    				" - Forma de pago: " . $forma_pago .
    				" - Estado en curso del pago: " . $detalle_estado;
    		}
    	}
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuration options'),
                    'icon' => 'icon-check'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Path'),
                        'name' => 'T_RUTA',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Service code'),
                        'name' => 'COD_SERVICIO',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Commerce Id'),
                        'name' => 'INT_ID_COMERCIO',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Key'),
                        'name' => 'CLAVE',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Commerce user'),
                        'name' => 'STR_USR_COMERCIO',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Commerce password'),
                        'name' => 'STR_PWD_COMERCIO',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Email'),
                        'name' => 'EMAIL',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Phone'),
                        'name' => 'PHONE',
                        'required' => true
                    ),
                ),
                'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
        );

        $this->fields_form = array();

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'T_RUTA' => Tools::getValue('T_RUTA', Configuration::get('T_RUTA')),
            'COD_SERVICIO' => Tools::getValue('COD_SERVICIO', Configuration::get('COD_SERVICIO')),
            'INT_ID_COMERCIO' => Tools::getValue('INT_ID_COMERCIO', Configuration::get('INT_ID_COMERCIO')),
            'CLAVE' => Tools::getValue('CLAVE', Configuration::get('CLAVE')),
            'STR_USR_COMERCIO' => Tools::getValue('STR_USR_COMERCIO', Configuration::get('STR_USR_COMERCIO')),
            'STR_PWD_COMERCIO' => Tools::getValue('STR_PWD_COMERCIO', Configuration::get('STR_PWD_COMERCIO')),
            'EMAIL' => Tools::getValue('EMAIL', Configuration::get('EMAIL')),
            'PHONE' => Tools::getValue('PHONE', Configuration::get('PHONE')),
        );
    }
}
