<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Description extends Module 
{
    public function __construct() 
    {
        $this->name = 'description';
        $this->author = 'Pawel Mazur';
        $this->version = '1.0.0';
        $this->tab = 'administration';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Description');
        $this->description = $this->l('Add additional description to product');
        $this->ps_version_compliancy = array('min' => '1.6.0.0', 'max' => _PS_VERSION_);

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install() 
    {
        if (!parent::install()) {
            return false;
        }

        return Configuration::updateValue('PS_ADD_DESCRIPTION', '')
            AND $this->registerHook('header')
            AND $this->registerHook('displayAdminProductsExtra')
            AND $this->registerHook('displayRightColumnProduct')
            AND $this->createProductAddDecription();
    }

    public function uninstall() 
    {
        if (!parent::uninstall()
            OR !Configuration::deleteByName('PS_ADD_DESCRIPTION')
            OR !$this->unregisterHook('header')
            OR !$this->unregisterHook('displayAdminProductsExtra')
            OR !$this->unregisterHook('displayRightColumnProduct')
            OR !$this->removeProductAddDecription()) {
                return false;
        }
        return true;
    }

    private function createProductAddDecription() 
    {
        $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product_lang ADD `add_description` TEXT NOT NULL';
        return Db::getInstance()->Execute($sql);
    }

    private function removeProductAddDecription() 
    {
        $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product_lang DROP COLUMN `add_description`';
        return Db::getInstance()->Execute($sql);
    }

    public function hookDisplayHeader($params){
        $this->context->controller->addCSS(($this->_path).'views/css/front_description.css', 'all');
        $this->context->controller->addJS(($this->_path).'views/js/front_description.js', 'all');
    }

    public function hookDisplayAdminProductsExtra($params) {
        $product = new Product((int)Tools::getValue('id_product'));
        if (Validate::isLoadedObject($product)) {   
            $html = '';            
            if (Tools::isSubmit('SUBMIT_ADD_DESCRIPTION')) {
                Configuration::updateValue('PS_ADD_DESCRIPTION', (string)Tools::getValue('PS_ADD_DESCRIPTION'));
                $query = 'UPDATE ' . _DB_PREFIX_. 'product_lang' . ' SET ' . 
                'add_description' . ' = '. "'" . pSQL(Configuration::get('PS_ADD_DESCRIPTION')) . "'" . ' WHERE ' . 
                'id_product' . ' = ' . (int)$product->id;
                if (Db::getInstance()->Execute($query)) {
                    $html .= $this->displayConfirmation($this->l('Product updated'));
                } else {
                    $html .- $this->displayError($this->l('Can not update product'));
                }
                // if(Db::getInstance()->Update(_DB_PREFIX_. 'product_lang', 
                //                           array('add_description' => "'" . pSQL(Configuration::get('PS_ADD_DESCRIPTION') . "'")),
                //                           'id_product = ' . (int)$product->id)) {
                //     $html .= $this->displayConfirmation($this->l('Product updated'));
                // } else {
                //     $html .- $this->displayError($this->l('Can not update product'));
                // }
            } else {
                $p_add_desc = Db::getInstance()->getRow('
                SELECT add_description FROM ' ._DB_PREFIX_. 'product_lang WHERE id_product = ' . (int)$product->id);
                Configuration::updateValue('PS_ADD_DESCRIPTION', $p_add_desc['add_description']);
            }
            return $html.$this->renderForm((int)$product->id);
        }
    }

    public function hookDisplayRightColumnProduct() {
        $product = new Product((int)Tools::getValue('id_product'));
        if (Validate::isLoadedObject($product)) {
            $id_product = (int)$product->id;
            if (Configuration::get('PS_ADD_DESCRIPTION')) {
                $p_add_desc = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT add_description FROM ' . _DB_PREFIX_ . 'product_lang 
                WHERE id_product = ' . $id_product);
                if (isset($p_add_desc['add_description'])) {
                    $add_description = $p_add_desc['add_description'];
                    $html = '';
                    $html .=    '<div class="add-description">' .
                                    '<h2>' . $add_description . '</h2>' .
                                '</div>';
    
                    return $html;
                }
                // if (isset($p_add_desc['add_description'])) {
                //     return $p_add_desc['add_description'];
                //     $this->context->smarty->assign(
                //         array(
                //             'add_description' => $p_add_desc['add_description']
                //         )
                //     );
                //     return $this->display(__FILE__, 'front_description.tpl');
                // }
            }
        }
    }

    public function renderForm($id_product) 
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Additional Description'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Description:'),
                        'desc' => $this->l('Add additional description for product'),
                        'name' => 'PS_ADD_DESCRIPTION',
                        'class' => 'form-control'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitAddproductAndStay'
                )
            )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'SUBMIT_ADD_DESCRIPTION';
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues()
        );
        return $helper->generateForm(array($fields_form));
    }

    private function getConfigFieldsValues()
	{
        return array(
            'PS_ADD_DESCRIPTION' => Tools::getValue('PS_ADD_DESCRIPTION', Configuration::get('PS_ADD_DESCRIPTION'))
        );
    }
}