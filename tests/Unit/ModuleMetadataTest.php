<?php

class ModuleMetadataTest extends SupplierListTestCase
{
    public function testConstructorPublishesModuleIdentity()
    {
        $module = $this->newModule();
        $this->assertSame('ps_supplierlist', $module->name);
        $this->assertSame('front_office_features', $module->tab);
        $this->assertSame('1.0.7', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertTrue((bool) $module->bootstrap);
        $this->assertSame('Supplier list', $module->displayName);
        $this->assertSame('blocksupplier', Ps_Supplierlist::PS_16_EQUIVALENT_MODULE);
    }

    public function testConstructorPublishesDescriptionCompliancyAndTemplate()
    {
        $module = $this->newModule();
        $this->assertSame(
            'Display your suppliers on your catalog and allow visitors to filter their search by supplier.',
            $module->description
        );
        $this->assertInternalType('array', $module->ps_versions_compliancy);
        $this->assertSame('1.7.0.0', $module->ps_versions_compliancy['min']);
        $this->assertSame(_PS_VERSION_, $module->ps_versions_compliancy['max']);

        $reflection = new ReflectionObject($module);
        $property = $reflection->getProperty('templateFile');
        $property->setAccessible(true);
        $this->assertSame($this->moduleTemplate(), $property->getValue($module));

        $this->assertNotEmpty(Module::$transLog);
        $domains = array();
        foreach (Module::$transLog as $entry) {
            $domains[] = $entry['domain'];
        }
        $this->assertContains('Modules.Supplierlist.Admin', $domains);
    }

    public function testConfigXmlMatchesLiteralMetadata()
    {
        $path = $this->repoRoot() . '/config.xml';
        $this->assertFileExists($path);
        $xml = file_get_contents($path);
        $this->assertInternalType('string', $xml);
        $this->assertNotSame('', $xml);
        $this->assertContains('<name>ps_supplierlist</name>', $xml);
        $this->assertContains('<version><![CDATA[1.0.7]]></version>', $xml);
        $this->assertContains('<tab><![CDATA[front_office_features]]></tab>', $xml);
        $this->assertContains('<need_instance>0</need_instance>', $xml);
    }

    public function testImplementsWidgetInterfaceContract()
    {
        $module = $this->newModule();
        $this->assertTrue($module instanceof PrestaShop\PrestaShop\Core\Module\WidgetInterface);

        $reflection = new ReflectionClass('Ps_Supplierlist');
        $renderWidget = $reflection->getMethod('renderWidget');
        $this->assertTrue($renderWidget->isPublic());
        $this->assertCount(2, $renderWidget->getParameters());
        $this->assertTrue($renderWidget->getParameters()[1]->isArray());

        $getWidgetVariables = $reflection->getMethod('getWidgetVariables');
        $this->assertTrue($getWidgetVariables->isPublic());
        $this->assertCount(2, $getWidgetVariables->getParameters());
        $this->assertTrue($getWidgetVariables->getParameters()[1]->isArray());
    }
}
