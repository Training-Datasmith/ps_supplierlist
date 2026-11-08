<?php

class GetContentTest extends SupplierListTestCase
{
    public function testUnsubmittedGetContentReturnsOnlyTheForm()
    {
        $module = new Ps_SupplierlistFormStub();
        $output = $module->getContent();
        $this->assertSame('HELPER_FORM_HTML', $output);
        $this->assertSame(array(), Configuration::getUpdates());
        $this->assertSame(array(), Module::$clearCacheCalls);
    }

    public function testSavesPlainTextListAndCastsCountToInt()
    {
        $_POST['submitBlockSuppliers'] = '1';
        $_POST['SUPPLIER_DISPLAY_TYPE'] = 'supplier_text';
        $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = '5';
        $module = new Ps_SupplierlistFormStub();
        $output = $module->getContent();
        $this->assertContains('The settings have been updated.', $output);
        $this->assertContains('alert alert-success', $output);
        $updates = Configuration::getUpdates();
        $this->assertCount(2, $updates);
        $this->assertSame('supplier_text', $updates[0]['value']);
        $this->assertSame(5, $updates[1]['value']);
        $this->assertCount(1, Module::$clearCacheCalls);
        $this->assertSame($this->moduleTemplate(), Module::$clearCacheCalls[0]['template']);
    }

    public function testSavesDropdownList()
    {
        $_POST['submitBlockSuppliers'] = '1';
        $_POST['SUPPLIER_DISPLAY_TYPE'] = 'supplier_form';
        $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = '12';
        $module = new Ps_SupplierlistFormStub();
        $this->assertTrue((bool) strpos($module->getContent(), 'The settings have been updated.'));
        $updates = Configuration::getUpdates();
        $this->assertSame('supplier_form', $updates[0]['value']);
        $this->assertSame(12, $updates[1]['value']);
    }

    /**
     * @dataProvider invalidCountProvider
     */
    public function testRejectsInvalidCountsWithoutSaving($textNb)
    {
        $_POST['submitBlockSuppliers'] = '1';
        $_POST['SUPPLIER_DISPLAY_TYPE'] = 'supplier_text';
        if ($textNb !== '__MISSING__') {
            $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = $textNb;
        }
        $module = new Ps_SupplierlistFormStub();
        $output = $module->getContent();
        $this->assertContains('Invalid number of elements.', $output);
        $this->assertContains('alert alert-danger', $output);
        $this->assertSame(array(), Configuration::getUpdates());
        $this->assertSame(array(), Module::$clearCacheCalls);
    }

    public function invalidCountProvider()
    {
        return array(
            array('5abc'),
            array('-1'),
            array(''),
            array('5.5'),
            array(' 5'),
            array('08'),
            array('__MISSING__'),
        );
    }

    /**
     * @dataProvider invalidTypeProvider
     */
    public function testRejectsInvalidTypesWithoutSaving($type)
    {
        $_POST['submitBlockSuppliers'] = '1';
        $_POST['SUPPLIER_DISPLAY_TYPE'] = $type;
        $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = '4';
        $module = new Ps_SupplierlistFormStub();
        $output = $module->getContent();
        $this->assertContains('Please activate at least one type of list.', $output);
        $this->assertNotContains('Invalid number of elements.', $output);
        $this->assertSame(array(), Configuration::getUpdates());
    }

    public function invalidTypeProvider()
    {
        return array(
            array('nope'),
            array(''),
            array('0'),
            array('supplier_text '),
            array('SUPPLIER_TEXT'),
        );
    }

    public function testSuccessfulSaveClearsModuleTemplateNotStarArgument()
    {
        $_POST['submitBlockSuppliers'] = '1';
        $_POST['SUPPLIER_DISPLAY_TYPE'] = 'supplier_text';
        $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = '3';
        $module = new Ps_SupplierlistFormStub();
        $module->getContent();
        $this->assertCount(1, Module::$clearCacheCalls);
        $this->assertSame($this->moduleTemplate(), Module::$clearCacheCalls[0]['template']);
    }

    public function testImageSubmitSuffixSavesSettings()
    {
        $_POST['submitBlockSuppliers_x'] = '1';
        $_POST['SUPPLIER_DISPLAY_TYPE'] = 'supplier_text';
        $_POST['SUPPLIER_DISPLAY_TEXT_NB'] = '2';
        $module = new Ps_SupplierlistFormStub();
        $module->getContent();
        $this->assertCount(2, Configuration::getUpdates());
    }
}
