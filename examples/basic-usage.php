<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_supplierlist PrestaShop module.
 *
 * ps_supplierlist displays a list of product suppliers with links to their
 * product listing pages. Useful for stores with multiple supplier brands
 * or drop-ship arrangements where supplier identity matters to customers.
 *
 * This file documents common usage patterns.
 */

// --- Widget invocation in Smarty/Twig template ---
// {widget name="ps_supplierlist" hook="displayLeftColumn"}

// --- Querying suppliers programmatically ---
// $suppliers = Supplier::getSuppliers(
//     id_lang: (int) Context::getContext()->language->id,
//     active: true,
// );
//
// foreach ($suppliers as $supplier) {
//     $link        = Context::getContext()->link;
//     $supplierUrl = $link->getSupplierLink($supplier['id_supplier'], $supplier['link_rewrite']);
//     $logoUrl     = _PS_SUPP_IMG_DIR_ . $supplier['id_supplier'] . '.jpg';
//
//     echo $supplier['name'] . ': ' . $supplierUrl . "\n";
// }

// --- Back Office: managing suppliers ---
// Catalog > Suppliers:
//   - Add/edit suppliers (name, description, logo, address)
//   - Associate products with suppliers via product sheets
//   - Set supplier reference codes per product combination

// --- Template override ---
// themes/{theme}/modules/ps_supplierlist/views/templates/hook/ps_supplierlist.tpl
