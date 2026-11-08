<?php

class SupplierListTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $_GET = array();
        $_POST = array();
        Configuration::reset();
        Module::resetState();
        Supplier::reset();
        Link::reset();
        HelperForm::reset();
        SmartyDataStub::reset();
        BlocksupplierDouble::resetCounters();
        Context::reset();
        Context::seed();
    }

    protected function tearDown()
    {
        $_GET = array();
        $_POST = array();
    }

    protected function newModule()
    {
        return new Ps_Supplierlist();
    }

    protected function moduleTemplate()
    {
        return 'module:ps_supplierlist/views/templates/hook/ps_supplierlist.tpl';
    }

    protected function assertIsStringNonEmpty($value, $message = '')
    {
        $this->assertInternalType('string', $value, $message);
        $this->assertNotSame('', $value, $message);
    }

    protected function assertNoPhpDiagnostic($text, $message = '')
    {
        $this->assertInternalType('string', (string) $text, $message);
        $pattern = '/(Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:)/';
        $this->assertSame(0, preg_match($pattern, (string) $text), $message);
    }

    protected function repoRoot()
    {
        return dirname(dirname(__DIR__));
    }
}

class Ps_SupplierlistFormStub extends Ps_Supplierlist
{
    public function renderForm()
    {
        return 'HELPER_FORM_HTML';
    }
}
