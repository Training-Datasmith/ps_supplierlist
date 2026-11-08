<?php

class WidgetTest extends SupplierListTestCase
{
    /**
     * @dataProvider disabledSupplierDisplayProvider
     */
    public function testRenderWidgetIsEmptyWhenSuppliersAreDisabled($storedValue)
    {
        if ($storedValue !== '__MISSING__') {
            Configuration::setStore(array('PS_DISPLAY_SUPPLIERS' => $storedValue));
        }
        $module = $this->newModule();
        $result = $module->renderWidget('displayLeftColumn', array());
        $this->assertTrue($result === null || $result === '');
        $this->assertSame(0, Supplier::$callCount);
        $this->assertSame(array(), Module::$fetchCalls);
    }

    public function disabledSupplierDisplayProvider()
    {
        return array(
            array('__MISSING__'),
            array('0'),
            array(''),
        );
    }

    public function testRenderWidgetFetchesOnCacheMiss()
    {
        Configuration::setStore(array(
            'PS_DISPLAY_SUPPLIERS' => '1',
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_text',
            'SUPPLIER_DISPLAY_TEXT_NB' => '5',
        ));
        Context::getContext()->language = new LanguageStub(6);
        Supplier::$rows = array(
            array('id_supplier' => 2, 'name' => 'Alpha', 'link_rewrite' => 'alpha'),
            array('id_supplier' => 10, 'name' => 'Bravo', 'link_rewrite' => 'bravo'),
        );
        $module = $this->newModule();
        $module->setCached($this->moduleTemplate(), 'ps_supplierlist|8', false);
        $result = $module->renderWidget('displayLeftColumn', array());
        $this->assertSame('rendered:' . $this->moduleTemplate() . '|ps_supplierlist|8', $result);
        $this->assertSame('LINK-SUPPLIER-2', SmartyDataStub::$assignments['suppliers'][0]['link']);
        $this->assertCount(1, Module::$fetchCalls);
        $this->assertSame($this->moduleTemplate(), Module::$fetchCalls[0]['template']);
        $this->assertSame('ps_supplierlist|8', Module::$fetchCalls[0]['cache_id']);
    }

    public function testRenderWidgetDoesNotRebuildVariablesWhenCached()
    {
        Configuration::setStore(array('PS_DISPLAY_SUPPLIERS' => '1'));
        $module = $this->newModule();
        $module->setCached($this->moduleTemplate(), 'ps_supplierlist|8', true);
        Supplier::$rows = array(array('id_supplier' => 1, 'name' => 'Only'));
        $module->renderWidget('displayRightColumn', array());
        $this->assertSame(0, Supplier::$callCount);
        $this->assertSame(array(), SmartyDataStub::$assignments);
        $this->assertCount(1, Module::$fetchCalls);
    }

    public function testGetWidgetVariablesPassesActiveSuppliersWithProducts()
    {
        Context::getContext()->language = new LanguageStub(6);
        $module = $this->newModule();
        $module->getWidgetVariables('displayLeftColumn', array());
        $this->assertInternalType('array', Supplier::$lastCall);
        $this->assertSame(
            array(false, 6, true, false, false, false, true),
            Supplier::$lastCall
        );
    }

    public function testGetWidgetVariablesBuildsLinksFromSupplierIds()
    {
        Supplier::$rows = array(
            array('id_supplier' => 2, 'name' => 'Alpha'),
            array('id_supplier' => 10, 'name' => 'Bravo'),
        );
        $module = $this->newModule();
        $vars = $module->getWidgetVariables('displayLeftColumn', array());
        $this->assertSame('LINK-SUPPLIER-2', $vars['suppliers'][0]['link']);
        $this->assertSame('LINK-SUPPLIER-10', $vars['suppliers'][1]['link']);
        $this->assertSame(2, Link::$supplierCalls[0]['resolvedId']);
        $this->assertSame(10, Link::$supplierCalls[1]['resolvedId']);
    }

    public function testGetWidgetVariablesPreservesRowFieldsAndOrder()
    {
        Supplier::$rows = array(
            array('id_supplier' => 2, 'name' => 'Alpha', 'link_rewrite' => 'alpha'),
            array('id_supplier' => 10, 'name' => 'Bravo', 'link_rewrite' => 'bravo'),
        );
        $module = $this->newModule();
        $vars = $module->getWidgetVariables('displayLeftColumn', array());
        $this->assertSame('Alpha', $vars['suppliers'][0]['name']);
        $this->assertSame('alpha', $vars['suppliers'][0]['link_rewrite']);
        $this->assertSame('Bravo', $vars['suppliers'][1]['name']);
    }

    public function testGetWidgetVariablesLeavesEmptyListUntouched()
    {
        Supplier::$rows = array();
        $module = $this->newModule();
        $vars = $module->getWidgetVariables('displayLeftColumn', array());
        $this->assertInternalType('array', $vars['suppliers']);
        $this->assertCount(0, $vars['suppliers']);
        $this->assertSame(array(), Link::$supplierCalls);
    }

    public function testGetWidgetVariablesAcceptsFalseWithoutWarning()
    {
        Supplier::$rows = false;
        $module = $this->newModule();
        $vars = $module->getWidgetVariables('displayLeftColumn', array());
        $this->assertArrayHasKey('suppliers', $vars);
        $this->assertTrue($vars['suppliers'] === false || $vars['suppliers'] === array());
    }

    public function testGetWidgetVariablesReturnsDisplayConfiguration()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_text',
            'SUPPLIER_DISPLAY_TEXT_NB' => '5',
            'PS_DISPLAY_SUPPLIERS' => '1',
        ));
        $module = $this->newModule();
        $vars = $module->getWidgetVariables('displayLeftColumn', array());
        $this->assertSame('supplier_text', $vars['supplier_display_type']);
        $this->assertSame('5', $vars['text_list_nb']);
        $this->assertSame('LINK-PAGE-supplier', $vars['page_link']);
        $this->assertSame('1', $vars['display_link_supplier']);
    }
}
