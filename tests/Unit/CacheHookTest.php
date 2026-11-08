<?php

class CacheHookTest extends SupplierListTestCase
{
    /**
     * @dataProvider supplierHookProvider
     */
    public function testSupplierHooksClearModuleTemplate($method)
    {
        $module = $this->newModule();
        $module->$method(array('object' => new stdClass()));
        $this->assertCount(1, Module::$clearCacheCalls);
        $this->assertSame($this->moduleTemplate(), Module::$clearCacheCalls[0]['template']);
    }

    public function supplierHookProvider()
    {
        return array(
            array('hookActionObjectSupplierAddAfter'),
            array('hookActionObjectSupplierUpdateAfter'),
            array('hookActionObjectSupplierDeleteAfter'),
        );
    }
}
