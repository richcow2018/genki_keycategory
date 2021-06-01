<?php
/**
 * 2021 Genkiware
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author     Genkiware <info@genkiware.com>
 *  @copyright  2021 Genkiware
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Genki_Keycategory extends Module
{
    public function __construct() {
        $this->name                   = 'genki_keycategory';
        $this->tab                    = 'checkout';
        $this->version                = '1.0';
        $this->author                 = 'Genkiware';
        $this->bootstrap              = true;
        
        parent::__construct();

        $this->displayName            = $this->l('Key Category Setting For Valid Orders');
        $this->description            = $this->l('This module restrict what category is needed for a valid order');
        $this->confirmUninstall       = $this->l('Are you sure to uninstall this module? This will cancel the key category setting.');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install(){
        Configuration::updateValue('GENKI_KEYCATEGORY_ACTIVE', '1');
        Configuration::updateValue('GENKI_KEYCATEGORY_MODE', '1');
        Configuration::updateValue('GENKI_KEYCATEGORY_CATE', '2');
        foreach (Language::getIDs(false) as $lang){
            $wrong_msg[$lang] = 'Sorry, but your cart is missing the key items';
        }
        Configuration::updateValue('GENKI_KEYCATEGORY_TEXT', $wrong_msg);

        return parent::install() &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayShoppingCart') &&
            $this->registerHook('displayCheckoutSummaryTop') &&
            $this->registerHook('actionObjectOrderAddBefore');
    }

    public function uninstall() {
        Configuration::deleteByName('GENKI_KEYCATEGORY_ACTIVE');
        Configuration::deleteByName('GENKI_KEYCATEGORY_MODE');
        Configuration::deleteByName('GENKI_KEYCATEGORY_CATE');
        Configuration::deleteByName('GENKI_KEYCATEGORY_TEXT');

        return parent::uninstall();
    }

    private function displayGenkiInfos() {
        $genki_logo = $this->_path.'views/img/genkiware/genkiware.png';
        $module_logo = $this->_path.'views/img/genkiware/banner-logo.png';

        $this->context->smarty->assign([
            'genki_logo' => $genki_logo,
            'module_logo' => $module_logo,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/genkiware_info.tpl'); 
    }

    public function renderForm() {
        // Get saved setting for selected category
        $selected_cat = explode(",",Configuration::get('GENKI_KEYCATEGORY_CATE'));

        // init fields for form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Key Category Setting'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'genki_desuka',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'genkidesu',
                            'value' => 1,
                            'label' => self::l('Enabled')
                        ),
                        array(
                            'id' => 'genkijyanai',
                            'value' => 0,
                            'label' => self::l('Disabled')
                        )
                    ),
                    'default' => '0',
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Select Mode'),
                    'name' => 'restrict_mode',
                    'id' => 'restrict_mode',
                    'required' => true,
                    'values' => array(
                        array(
                            'id' => 'mode_or',
                            'value' => 1,
                            'label' => $this->l('The order should contain ONE of the selected key categories'),
                        ),
                        array(
                            'id' => 'mode_and',
                            'value' => 2,
                            'label' => $this->l('The order should contain ALL selected key categories'),
                        ),
                    ),
                ),
                array(
                    'type'  => 'categories',
                    'label' => $this->l('Key Category'),
                    'name'  => 'key_category',
                    'default' => '',
                    'required' => true,
                    'col' => 6,
                    'tree'  => array(
                        'id'                  => 'categories',
                        'title'               => 'Categories',
                        'selected_categories' => $selected_cat,
                        'use_search'          => true,
                        'use_checkbox'        => true,
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Message for Invalid Order'),
                    'name' => 'wrong_msg',
                    'id' => 'wrong_msg',
                    'desc' => $this->l('To be shown if the order is invalid'),
                    'lang' => true,
                    'default' => '',
                    'required' => true,
                    'autoload_rte' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        // load helperForm
        $helper = new HelperForm();

        // module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Language
        foreach (Language::getLanguages(false) as $lang){
            $helper->languages[] = [
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($this->context->language->id == $lang['id_lang'] ? 1 : 0)
            ];
        }
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        // load current value
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];


        // CSS & JS
        $this->context->controller->addCSS($this->_path.'assets/css/genki_info.css');

        return $this->displayGenkiInfos() . $helper->generateForm($fields_form);
    }

    public function getConfigFieldsValues() {
        $fields = [
            'genki_desuka' => Configuration::get('GENKI_KEYCATEGORY_ACTIVE'),
            'restrict_mode' => Configuration::get('GENKI_KEYCATEGORY_MODE'),

        ];

        foreach (Language::getIDs(false) as $lang){
            $fields['wrong_msg'][$lang] = Configuration::get('GENKI_KEYCATEGORY_TEXT', $lang, null, null, '');
        }

        return $fields;
    }

    public function getContent() {
        $output = '';

        // here we check if the form is submited for this module
        if (Tools::isSubmit('submit'.$this->name)) {
            $config['GENKI_KEYCATEGORY_ACTIVE'] = Tools::getValue('genki_desuka');
            $config['GENKI_KEYCATEGORY_MODE'] = Tools::getValue('restrict_mode');
            $key_cat = Tools::getValue('key_category');
            $config['GENKI_KEYCATEGORY_CATE'] = !$key_cat ? '2': implode(",",$key_cat);
            foreach (Language::getIDs(false) as $lang){
                $config['GENKI_KEYCATEGORY_TEXT'][$lang] = Tools::getValue('wrong_msg_'.$lang);
            }
            
            foreach ($config as $key => $value){
                Configuration::updateValue($key, $value);
            }

            $output .= $this->displayConfirmation($this->l('Key Category Setting Updated!'));
        }
        return $output.$this->renderForm();
    }

    public function hookActionCartSave($params) {
        // Skip if module not activate
        $active = Configuration::get('GENKI_KEYCATEGORY_ACTIVE');
        if (!$active) return;

        $cart = $this->context->cart;
        if (isset($cart)) {
            $products = $cart->getProducts(false,false,null,false);
            $hasKeyCat = $this->isValidOrder($products);
            $this->context->cookie->hasKeyCat = $hasKeyCat;
        }
        return;
    }

    public function hookActionFrontControllerSetMedia($params) {
        // Skip if module not activate
        $active = Configuration::get('GENKI_KEYCATEGORY_ACTIVE');
        if (!$active) return;

        $lang = $this->context->language->id;
        $wrong_msg = Configuration::get('GENKI_KEYCATEGORY_TEXT', $lang);
        $page_name = $this->context->controller->getPageName();
        switch ($page_name) {
            case 'cart':
                if (Tools::getValue('noKeyCategory')) {
                    Media::addJsDef(['wrong_msg' => $wrong_msg]);
                    $this->context->controller->addJS($this->_path.'assets/js/alert_invalid_order.js');
                }
                $hasKeyCat = $this->context->cookie->hasKeyCat;
                if (!$hasKeyCat) $this->context->controller->addJS($this->_path.'assets/js/disable_cart_checkout.js');
                break;

            case 'checkout':
                $hasKeyCat = $this->context->cookie->hasKeyCat;
                if (!$hasKeyCat) $this->context->controller->addJS($this->_path.'assets/js/disable_checkout_payment.js');
                break;
        }
        return;
    }

    public function hookDisplayShoppingCart($params) {
        // Skip if module not activate
        $active = Configuration::get('GENKI_KEYCATEGORY_ACTIVE');
        if (!$active) return;

        $lang = $this->context->language->id;
        $wrong_msg = Configuration::get('GENKI_KEYCATEGORY_TEXT', $lang);
        if (!$this->context->cookie->hasKeyCat) {
            $msg = '<div class="card-block">' . $this->displayError($wrong_msg) . '</div>';
            return $msg;
        }
    }

    public function hookDisplayCheckoutSummaryTop($params) {
        // Skip if module not activate
        $active = Configuration::get('GENKI_KEYCATEGORY_ACTIVE');
        if (!$active) return;

        $lang = $this->context->language->id;
        $wrong_msg = Configuration::get('GENKI_KEYCATEGORY_TEXT', $lang);
        if (!$this->context->cookie->hasKeyCat) {
            $msg = $this->displayError($wrong_msg);
            return $msg;
        }
    }

    public function hookActionObjectOrderAddBefore($params) {
        // Skip if module not activate
        $active = Configuration::get('GENKI_KEYCATEGORY_ACTIVE');
        if (!$active) return;
        
        $cart = $params['cart'];
        $products = $cart->getProducts(false,false,null,false);        
        $hasKeyCat = $this->isValidOrder($products);
        if ($hasKeyCat) {
            return;
        } else {
            return Tools::redirect('index.php?controller=cart&action=show&noKeyCategory=1');
        }
    }

    private function isValidOrder($products) {
        // Get module configuration;
        $mode = Configuration::get('GENKI_KEYCATEGORY_MODE');
        $key_cat = explode(",",Configuration::get('GENKI_KEYCATEGORY_CATE'));

        switch ($mode) {
            case '1':
                # Mode OR
                foreach ($products as $product) {
                    $id_product = $product['id_product'];
                    $categories = Product::getProductCategories($id_product);
                    foreach ($categories as $category) {
                        if (in_array($category, $key_cat)) return true;
                    }
                }
                break;
            
            case '2':
                # Mode AND
                // Get full list of categories of all products in cart
                $categories = [];
                foreach ($products as $product) {
                    $id_product = $product['id_product'];
                    $categories = array_merge($categories, Product::getProductCategories($id_product));
                }
                $categories = array_intersect($key_cat, $categories);
                if ($key_cat == $categories) return true;
        }
        return false;
    }
}