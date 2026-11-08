<?php

class InstallUninstallTest extends SupplierListTestCase
{
    public function testInstallSetsMissingDefaultsAndRegistersHooks()
    {
        $module = $this->newModule();
        $this->assertTrue($module->install());
        $keys = array();
        foreach (Configuration::getUpdates() as $update) {
            $keys[$update['key']] = $update['value'];
        }
        $this->assertArrayHasKey('SUPPLIER_DISPLAY_TEXT_NB', $keys);
        $this->assertArrayHasKey('SUPPLIER_DISPLAY_TYPE', $keys);
        $this->assertSame(5, $keys['SUPPLIER_DISPLAY_TEXT_NB']);
        $this->assertSame('supplier_text', $keys['SUPPLIER_DISPLAY_TYPE']);
        $expected = array(
            'displayLeftColumn',
            'displayRightColumn',
            'actionObjectSupplierDeleteAfter',
            'actionObjectSupplierAddAfter',
            'actionObjectSupplierUpdateAfter',
        );
        sort($expected);
        $hooks = Module::$registeredHooks;
        sort($hooks);
        $this->assertSame($expected, $hooks);
    }

    public function testInstallPreservesTruthyTextNb()
    {
        Configuration::setStore(array('SUPPLIER_DISPLAY_TEXT_NB' => '8'));
        $module = $this->newModule();
        $this->assertTrue($module->install());
        $this->assertSame('8', Configuration::get('SUPPLIER_DISPLAY_TEXT_NB'));
    }

    public function testInstallDoesNotRegisterHooksWhenParentInstallFails()
    {
        Module::$parentInstallResult = false;
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertSame(array(), Module::$registeredHooks);
    }

    /**
     * @dataProvider hookFailureProvider
     */
    public function testInstallAbortsWhenHookRegistrationFails($failingHook)
    {
        Module::setRegisterHookFailures(array($failingHook));
        $module = $this->newModule();
        $this->assertFalse($module->install());
    }

    public function hookFailureProvider()
    {
        return array(
            array('displayLeftColumn'),
            array('displayRightColumn'),
            array('actionObjectSupplierDeleteAfter'),
            array('actionObjectSupplierAddAfter'),
            array('actionObjectSupplierUpdateAfter'),
        );
    }

    public function testUninstallRemovesBothKeysAfterParentSucceeds()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_text',
            'SUPPLIER_DISPLAY_TEXT_NB' => '5',
        ));
        $module = $this->newModule();
        $this->assertTrue($module->uninstall());
        $this->assertFalse(Configuration::hasKey('SUPPLIER_DISPLAY_TYPE'));
        $this->assertFalse(Configuration::hasKey('SUPPLIER_DISPLAY_TEXT_NB'));
    }

    public function testUninstallLeavesKeysWhenParentFails()
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_text',
            'SUPPLIER_DISPLAY_TEXT_NB' => '5',
        ));
        Module::$parentUninstallResult = false;
        $module = $this->newModule();
        $this->assertFalse($module->uninstall());
        $this->assertTrue(Configuration::hasKey('SUPPLIER_DISPLAY_TYPE'));
        $this->assertTrue(Configuration::hasKey('SUPPLIER_DISPLAY_TEXT_NB'));
    }

    /**
     * @dataProvider uninstallDeleteFailureProvider
     */
    public function testUninstallReturnsFalseWhenDeleteFails($failKey)
    {
        Configuration::setStore(array(
            'SUPPLIER_DISPLAY_TYPE' => 'supplier_text',
            'SUPPLIER_DISPLAY_TEXT_NB' => '5',
        ));
        Configuration::setDeleteFails(array($failKey));
        $module = $this->newModule();
        $this->assertFalse($module->uninstall());
        $this->assertTrue(Configuration::hasKey($failKey));
    }

    public function uninstallDeleteFailureProvider()
    {
        return array(
            array('SUPPLIER_DISPLAY_TYPE'),
            array('SUPPLIER_DISPLAY_TEXT_NB'),
        );
    }

    public function testLegacyModuleParentUninstallIsBoundToOldInstance()
    {
        Module::setInstalled('blocksupplier', true);
        $legacy = new BlocksupplierDouble();
        Module::setInstance('blocksupplier', $legacy);
        $module = $this->newModule();
        $this->assertTrue($module->uninstallPrestaShop16Module());
        $this->assertSame(1, Module::$parentUninstallCount);
        $this->assertSame(0, BlocksupplierDouble::$childUninstallCount);
        $this->assertSame($legacy, Module::$lastUninstallInstance);
    }

    public function testLegacyUninstallReturnsFalseWhenModuleIsNotInstalled()
    {
        Module::setInstalled('blocksupplier', false);
        $module = $this->newModule();
        $this->assertFalse($module->uninstallPrestaShop16Module());
        $this->assertSame(0, Module::$parentUninstallCount);
        $this->assertNull(Module::$lastUninstallInstance);
    }

    public function testInstallUninstallsInstalledLegacyModuleThroughParent()
    {
        Module::setInstalled('blocksupplier', true);
        $legacy = new BlocksupplierDouble();
        Module::setInstance('blocksupplier', $legacy);
        $module = $this->newModule();
        $this->assertTrue($module->install());
        $this->assertSame(1, Module::$parentUninstallCount);
        $this->assertSame(0, BlocksupplierDouble::$childUninstallCount);
        $this->assertSame($legacy, Module::$lastUninstallInstance);
    }
}
