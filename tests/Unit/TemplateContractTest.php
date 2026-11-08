<?php

class TemplateContractTest extends SupplierListTestCase
{
    public function testAllExpectedTemplatesExistAndAreNonempty()
    {
        $paths = array(
            'views/templates/hook/ps_supplierlist.tpl',
            'views/templates/_partials/supplier_text.tpl',
            'views/templates/_partials/supplier_form.tpl',
        );
        foreach ($paths as $relative) {
            $path = $this->repoRoot() . '/' . $relative;
            $this->assertFileExists($path);
            $contents = file_get_contents($path);
            $this->assertInternalType('string', $contents);
            $this->assertNotSame('', $contents);
        }
    }

    public function testHookTemplateHasSupplierAndEmptyBranches()
    {
        $contents = $this->normalizedTemplate('views/templates/hook/ps_supplierlist.tpl');
        $this->assertContains('{if $suppliers}', $contents);
        $this->assertContains('{else}', $contents);
        $this->assertContains('No supplier', $contents);
    }

    public function testHookTemplateControlsTitleLinkWithFlag()
    {
        $contents = $this->normalizedTemplate('views/templates/hook/ps_supplierlist.tpl');
        $this->assertContains('{if $display_link_supplier}', $contents);
        $this->assertContains('$page_link', $contents);
    }

    public function testHookTemplateIncludesConfiguredPartial()
    {
        $contents = $this->normalizedTemplate('views/templates/hook/ps_supplierlist.tpl');
        $this->assertContains('module:ps_supplierlist/views/templates/_partials/$supplier_display_type.tpl', $contents);
        $this->assertContains('suppliers=$suppliers', $contents);
    }

    public function testTextPartialLimitsVisibleSuppliers()
    {
        $contents = $this->normalizedTemplate('views/templates/_partials/supplier_text.tpl');
        $this->assertRegExp(
            '/\{foreach[^}]*from=\$suppliers[^}]*item=supplier[^}]*name=supplier_list[^}]*\}/',
            $contents
        );
        $this->assertContains('$smarty.foreach.supplier_list.iteration <= $text_list_nb', $contents);
        $this->assertRegExp('/\{\$supplier\[\'link\'\](\|[^}]*)?\}/', $contents);
        $this->assertRegExp('/\{\$supplier\[\'name\'\](\|[^}]*)?\}/', $contents);
    }

    public function testDropdownPartialListsEverySupplier()
    {
        $contents = $this->normalizedTemplate('views/templates/_partials/supplier_form.tpl');
        $this->assertRegExp('/\{foreach[^}]*from=\$suppliers[^}]*item=supplier[^}]*\}/', $contents);
        $this->assertContains('All suppliers', $contents);
        $this->assertRegExp('/\{\$supplier\[\'link\'\](\|[^}]*)?\}/', $contents);
        $this->assertRegExp('/\{\$supplier\[\'name\'\](\|[^}]*)?\}/', $contents);
        $this->assertNotContains('text_list_nb', $contents);
    }

    private function normalizedTemplate($relative)
    {
        $raw = file_get_contents($this->repoRoot() . '/' . $relative);
        $this->assertInternalType('string', $raw);
        $withoutComments = preg_replace('/\{\\*.*?\\*\\}/s', '', $raw);
        $this->assertInternalType('string', $withoutComments);

        return preg_replace('/\\s+/', ' ', $withoutComments);
    }
}
