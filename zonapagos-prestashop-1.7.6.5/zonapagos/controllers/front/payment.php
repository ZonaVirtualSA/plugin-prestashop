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
class ZonapagosPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function init()
    {
        parent::init();
        $this->values['message'] = Tools::getvalue('message');
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        Context::getContext()->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int)$cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'isoCode' => $this->context->language->iso_code,
            't_ruta' => $this->module->t_ruta,
            'cod_servicio' => $this->module->cod_servicio,
            'int_id_comercio' => $this->module->int_id_comercio,
            'clave' => $this->module->clave,
            'str_usr_comercio' => $this->module->str_usr_comercio,
            'str_pwd_Comercio' => $this->module->str_pwd_Comercio,
            'email' => $this->module->email,
            'phone' => $this->module->phone,
            'message' => $this->values['message'],
            'this_path' => $this->module->getPathUri(),
            'this_path_check' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
        ));
        $this->setTemplate('module:zonapagos/views/templates/front/payment_execution.tpl');
    }
}
