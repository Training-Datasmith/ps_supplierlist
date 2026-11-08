<?php

class ConfigFormTest extends SupplierListTestCase
{
    public function testGetConfigFieldsFallsBackToConfiguration()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_form',
            'SUPPLIER_DISPLAY_TEXT_NB' => '4',
        ));
        $module = $this->newModule();
        $values = $module->getConfigFieldsValues();
        $this->assertInternalType('array', $values);
        $this->assertSame('supplier_form', $values['SUPPLIER_DISPLAY_TYPE']);
        $this->assertSame('4', $values['SUPPLIER_DISPLAY_TEXT_NB']);
    }

    public function testGetConfigFieldsPrefersPostedValues()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_form',
            'SUPPLIER_DISPLAY_TEXT_NB' => '4',
        ));
        $_POST['SUPPLIER_DISPLAY_TYPE'] = 'supplier_text';
        $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = '9';
        $module = $this->newModule();
        $values = $module->getConfigFieldsValues();
        $this->assertSame('supplier_text', $values['SUPPLIER_DISPLAY_TYPE']);
        $this->assertSame('9', $values['SUPPLIER_DISPLAY_TEXT_NB']);
    }

    public function testEmptyPostedValueDoesNotUseConfiguration()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_text',
            'SUPPLIER_DISPLAY_TEXT_NB' => '4',
        ));
        $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = '';
        $module = $this->newModule();
        $values = $module->getConfigFieldsValues();
        $this->assertSame('', $values['SUPPLIER_DISPLAY_TEXT_NB']);
    }

    public function testPartialPostKeepsStoredCount()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_form',
            'SUPPLIER_DISPLAY_TEXT_NB' => '4',
        ));
        $_POST['SUPPLIER_DISPLAY_TYPE'] = 'supplier_text';
        $module = $this->newModule();
        $values = $module->getConfigFieldsValues();
        $this->assertSame('supplier_text', $values['SUPPLIER_DISPLAY_TYPE']);
        $this->assertSame('4', $values['SUPPLIER_DISPLAY_TEXT_NB']);
    }

    public function testRenderFormReturnsHelperHtmlAndFieldDefinitions()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_text',
            'SUPPLIER_DISPLAY_TEXT_NB' => '5',
        ));
        Context::getContext()->controller->languages = array('en', 'de');
        Context::getContext()->language = new LanguageStub(7);
        $module = $this->newModule();
        $html = $module->renderForm();
        $this->assertSame('HELPER_FORM_HTML', $html);

        $this->assertFalse(HelperForm::$lastConfig['show_toolbar']);
        $this->assertSame('module', HelperForm::$lastConfig['table']);
        $this->assertSame('id_module', HelperForm::$lastConfig['identifier']);
        $this->assertSame('submitBlockSuppliers', HelperForm::$lastConfig['submit_action']);
        $this->assertSame(7, HelperForm::$lastConfig['tpl_vars']['id_language']);
        $this->assertSame(array('en', 'de'), HelperForm::$lastConfig['tpl_vars']['languages']);
        $this->assertSame('supplier_text', HelperForm::$lastConfig['tpl_vars']['fields_value']['SUPPLIER_DISPLAY_TYPE']);
        $this->assertSame('5', HelperForm::$lastConfig['tpl_vars']['fields_value']['SUPPLIER_DISPLAY_TEXT_NB']);

        $form = HelperForm::$lastFields[0]['form'];
        $this->assertSame('Settings', $form['legend']['title']);
        $this->assertSame('Save', $form['submit']['title']);
        $this->assertSame('SUPPLIER_DISPLAY_TYPE', $form['input'][0]['name']);
        $this->assertSame('SUPPLIER_DISPLAY_TEXT_NB', $form['input'][1]['name']);
        $this->assertSame('supplier_text', $form['input'][0]['options']['query'][0]['id']);
        $this->assertSame('supplier_form', $form['input'][0]['options']['query'][1]['id']);
    }

    public function testRenderFormUsesCastLanguageId()
    {
        Configuration::setStore(array('PS_LANG_DEFAULT' => '3'));
        $module = $this->newModule();
        $module->renderForm();
        $this->assertSame(3, HelperForm::$lastConfig['default_form_language']);
    }

    public function testRenderFormEmployeeLangFallsBackToZero()
    {
        $module = $this->newModule();
        $module->renderForm();
        $this->assertSame(0, HelperForm::$lastConfig['allow_employee_form_lang']);
    }

    public function testRenderFormPassesThroughEmployeeLangValue()
    {
        Configuration::setStore(array('PS_BO_ALLOW_EMPLOYEE_FORM_LANG' => '4'));
        $module = $this->newModule();
        $module->renderForm();
        $this->assertSame('4', HelperForm::$lastConfig['allow_employee_form_lang']);
    }

    public function testRenderFormCurrentIndexAndToken()
    {
        $module = $this->newModule();
        $module->renderForm();
        $this->assertCount(1, Link::$adminCalls);
        $this->assertSame('AdminModules', Link::$adminCalls[0]['controller']);
        $this->assertFalse(Link::$adminCalls[0]['withToken']);
        $this->assertContains('configure=ps_supplierlist', HelperForm::$lastConfig['currentIndex']);
        $this->assertContains('tab_module=front_office_features', HelperForm::$lastConfig['currentIndex']);
        $this->assertContains('module_name=ps_supplierlist', HelperForm::$lastConfig['currentIndex']);
        $this->assertCount(1, Link::$tokenCalls);
        $this->assertSame('AdminModules', Link::$tokenCalls[0]['tab']);
        $this->assertSame('TOKEN-AdminModules', HelperForm::$lastConfig['token']);
    }
}
