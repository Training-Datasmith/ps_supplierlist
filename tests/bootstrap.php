<?php
/**
 * PHPUnit bootstrap: PrestaShop doubles, then the real module.
 */

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.11');
}

if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', dirname(__DIR__) . '/');
}

if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(__DIR__) . '/');
}

if (!defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', false);
}

if (!defined('_PS_THEME_DIR_')) {
    define('_PS_THEME_DIR_', dirname(__DIR__) . '/views/');
}

if (!defined('_PS_PARENT_THEME_DIR_')) {
    define('_PS_PARENT_THEME_DIR_', dirname(__DIR__) . '/views/');
}

require_once dirname(__FILE__) . '/doubles/PrestaShopDoubles.php';
require_once dirname(__FILE__) . '/doubles/BlocksupplierDouble.php';
require_once dirname(__DIR__) . '/ps_supplierlist.php';
require_once dirname(__FILE__) . '/Unit/SupplierListTestCase.php';
