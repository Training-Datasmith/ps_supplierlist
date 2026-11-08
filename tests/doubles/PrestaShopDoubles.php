<?php

namespace PrestaShop\PrestaShop\Core\Module {

interface WidgetInterface
{
    public function renderWidget($hookName, array $configuration);

    public function getWidgetVariables($hookName, array $configuration);
}

}

namespace {

class AdminControllerStub
{
    public $languages = array('en', 'fr');

    public function getLanguages()
    {
        return $this->languages;
    }
}

class CountryStub
{
    public $id;

    public function __construct($id)
    {
        $this->id = (int) $id;
    }
}

class LanguageStub
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}

class ShopStub
{
    public $id = 1;
}

class Context
{
    /** @var Context */
    private static $instance;

    public $language;
    public $country;
    public $shop;
    /** @var Link */
    public $link;
    public $controller;
    /** @var SmartyStub */
    public $smarty;

    public static function getContext()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset()
    {
        self::$instance = null;
    }

    public static function seed()
    {
        $ctx = self::getContext();
        $ctx->language = new LanguageStub(1);
        $ctx->country = new CountryStub(8);
        $ctx->shop = new ShopStub();
        $ctx->link = new Link();
        $ctx->controller = new AdminControllerStub();
        $ctx->smarty = new SmartyStub();
    }
}

class Configuration
{
    private static $store = array();
    private static $updateFailKeys = array();
    private static $deleteFailKeys = array();
    private static $updates = array();

    public static function reset()
    {
        self::$store = array();
        self::$updateFailKeys = array();
        self::$deleteFailKeys = array();
        self::$updates = array();
    }

    public static function setStore(array $values)
    {
        self::$store = $values;
    }

    public static function setUpdateFails(array $keys)
    {
        self::$updateFailKeys = $keys;
    }

    public static function setDeleteFails(array $keys)
    {
        self::$deleteFailKeys = $keys;
    }

    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
    {
        if (array_key_exists($key, self::$store)) {
            return self::$store[$key];
        }

        return $default;
    }

    public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        if (in_array($key, self::$updateFailKeys, true)) {
            return false;
        }

        self::$updates[] = array('key' => $key, 'value' => $values);
        self::$store[$key] = $values;

        return true;
    }

    public static function deleteByName($key)
    {
        if (in_array($key, self::$deleteFailKeys, true)) {
            return false;
        }

        unset(self::$store[$key]);

        return true;
    }

    public static function getUpdates()
    {
        return self::$updates;
    }

    public static function hasKey($key)
    {
        return array_key_exists($key, self::$store);
    }
}


class Validate
{
    public static function isUnsignedInt($value)
    {
        return (is_numeric($value) || is_string($value))
            && (string) (int) $value === (string) $value
            && $value < 4294967296
            && $value >= 0;
    }
}

class Shop
{
    public static function isFeatureActive()
    {
        return false;
    }
}

class Group
{
    public static function isFeatureActive()
    {
        return false;
    }

    public static function getCurrent()
    {
        return new stdClass();
    }
}

class Customer
{
    public static function getGroupsStatic($idCustomer)
    {
        return array();
    }
}

class Language
{
    public static function isMultiLanguageActivated()
    {
        return false;
    }

    public $id;

    public function __construct($id)
    {
        $this->id = (int) $id;
    }
}

class Currency
{
    public static function isMultiCurrencyActivated()
    {
        return false;
    }
}

class Supplier
{
    public static $rows = array();
    public static $lastCall = null;
    public static $callCount = 0;

    public static function reset()
    {
        self::$rows = array();
        self::$lastCall = null;
        self::$callCount = 0;
    }

    public static function getSuppliers(
        $getNbProducts = false,
        $idLang = 0,
        $active = true,
        $p = false,
        $n = false,
        $allGroups = false,
        $withProduct = false
    ) {
        self::$lastCall = func_get_args();
        ++self::$callCount;

        return self::$rows;
    }
}

class Link
{
    public static $supplierCalls = array();
    public static $pageCalls = array();
    public static $adminCalls = array();
    public static $tokenCalls = array();

    public static function reset()
    {
        self::$supplierCalls = array();
        self::$pageCalls = array();
        self::$adminCalls = array();
        self::$tokenCalls = array();
    }

    public function getSupplierLink($supplier, $alias = null, $idLang = null, $idShop = null, $relativeProtocol = false)
    {
        if (is_array($supplier)) {
            $id = 1;
        } elseif (is_object($supplier)) {
            $id = (int) $supplier->id;
        } else {
            $id = (int) $supplier;
        }

        self::$supplierCalls[] = array(
            'supplier' => $supplier,
            'alias' => $alias,
            'idLang' => $idLang,
            'idShop' => $idShop,
            'relativeProtocol' => $relativeProtocol,
            'resolvedId' => $id,
        );

        return 'LINK-SUPPLIER-' . $id;
    }

    public function getPageLink($controller, $ssl = null, $idLang = null, $request = null, $requestUrlEncode = false, $idShop = null, $relativeProtocol = false)
    {
        self::$pageCalls[] = array(
            'controller' => $controller,
            'ssl' => $ssl,
            'idLang' => $idLang,
            'request' => $request,
            'requestUrlEncode' => $requestUrlEncode,
            'idShop' => $idShop,
            'relativeProtocol' => $relativeProtocol,
        );

        return 'LINK-PAGE-' . $controller;
    }

    public function getAdminLink($controller, $withToken = true, $sfRouteParams = array(), $params = array())
    {
        self::$adminCalls[] = array(
            'controller' => $controller,
            'withToken' => $withToken,
            'sfRouteParams' => $sfRouteParams,
            'params' => $params,
        );

        return 'LINK-ADMIN-' . $controller . ($withToken ? '-token' : '-notoken');
    }
}

class Tools
{
    public static function usingSecureMode()
    {
        return false;
    }

    public static function getValue($key, $default_value = false)
    {
        if (empty($key) || !is_string($key)) {
            return false;
        }

        if (isset($_POST[$key])) {
            $value = $_POST[$key];
        } elseif (isset($_GET[$key])) {
            $value = $_GET[$key];
        } else {
            $value = $default_value;
        }

        if (is_string($value)) {
            return urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($value)));
        }

        return $value;
    }

    public static function isSubmit($submit)
    {
        return isset($_POST[$submit]) || isset($_POST[$submit . '_x']) || isset($_POST[$submit . '_y'])
            || isset($_GET[$submit]) || isset($_GET[$submit . '_x']) || isset($_GET[$submit . '_y']);
    }

    public static function getAdminTokenLite($tab, Context $context = null)
    {
        Link::$tokenCalls[] = array('tab' => $tab, 'context' => $context);

        return 'TOKEN-' . $tab;
    }
}

class HelperForm
{
    public static $lastFields = null;
    public static $lastConfig = array();

    public $show_toolbar;
    public $table;
    public $default_form_language;
    public $allow_employee_form_lang;
    public $identifier;
    public $submit_action;
    public $currentIndex;
    public $token;
    public $tpl_vars = array();

    public static function reset()
    {
        self::$lastFields = null;
        self::$lastConfig = array();
    }

    public function generateForm($fields)
    {
        self::$lastFields = $fields;
        self::$lastConfig = array(
            'show_toolbar' => $this->show_toolbar,
            'table' => $this->table,
            'default_form_language' => $this->default_form_language,
            'allow_employee_form_lang' => $this->allow_employee_form_lang,
            'identifier' => $this->identifier,
            'submit_action' => $this->submit_action,
            'currentIndex' => $this->currentIndex,
            'token' => $this->token,
            'tpl_vars' => $this->tpl_vars,
        );

        return 'HELPER_FORM_HTML';
    }
}

class SmartyDataStub
{
    public static $assignments = array();

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::$assignments[$k] = $v;
            }
        } else {
            self::$assignments[$key] = $value;
        }
    }

    public static function reset()
    {
        self::$assignments = array();
    }
}

class SmartyStub
{
    public function createData($parent = null)
    {
        return new SmartyDataStub();
    }
}

class Module
{
    public $id = null;
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $bootstrap;
    public $displayName;
    public $description;
    public $ps_versions_compliancy = array();
    public $context;
    public $smarty;
    /** @var bool */
    public $error = false;

    protected $table = 'module';
    protected $identifier = 'id_module';

    public static $parentInstallResult = true;
    public static $parentUninstallResult = true;
    public static $parentInstallCount = 0;
    public static $parentUninstallCount = 0;
    public static $installedModules = array();
    public static $moduleInstances = array();
    public static $registerHookResults = array();
    public static $registerHookFailures = array();
    public static $registeredHooks = array();
    public static $lastUninstallInstance = null;
    public static $clearCacheCalls = array();
    public static $fetchCalls = array();
    public static $cachedMap = array();
    public static $transLog = array();

    public function __construct($name = null, Context $context = null)
    {
        $this->context = $context ? $context : Context::getContext();
        if (is_object($this->context->smarty)) {
            $this->smarty = $this->context->smarty->createData($this->context->smarty);
        }
    }

    public static function resetState()
    {
        self::$parentInstallResult = true;
        self::$parentUninstallResult = true;
        self::$parentInstallCount = 0;
        self::$parentUninstallCount = 0;
        self::$installedModules = array();
        self::$moduleInstances = array();
        self::$registerHookResults = array();
        self::$registerHookFailures = array();
        self::$registeredHooks = array();
        self::$lastUninstallInstance = null;
        self::$clearCacheCalls = array();
        self::$fetchCalls = array();
        self::$cachedMap = array();
        self::$transLog = array();
    }

    public static function setInstalled($name, $installed = true)
    {
        self::$installedModules[$name] = (bool) $installed;
    }

    public static function setInstance($name, $instance)
    {
        self::$moduleInstances[$name] = $instance;
    }

    public static function setRegisterHookResults(array $results)
    {
        self::$registerHookResults = array_values($results);
    }

    public static function setRegisterHookFailures(array $hookNames)
    {
        self::$registerHookFailures = array_values($hookNames);
    }

    public static function isInstalled($name)
    {
        return !empty(self::$installedModules[$name]);
    }

    public static function getInstanceByName($name)
    {
        return isset(self::$moduleInstances[$name]) ? self::$moduleInstances[$name] : false;
    }

    public function install()
    {
        ++self::$parentInstallCount;
        if (!self::$parentInstallResult) {
            $this->id = 9001;

            return false;
        }
        $this->id = 9001;

        return true;
    }

    public function uninstall()
    {
        ++self::$parentUninstallCount;
        self::$lastUninstallInstance = $this;

        return self::$parentUninstallResult;
    }

    public function registerHook($hook_name, $shop_list = null)
    {
        if (!is_numeric($this->id)) {
            return false;
        }

        if (in_array($hook_name, self::$registerHookFailures, true)) {
            return false;
        }

        $result = true;
        if (count(self::$registerHookResults) > 0) {
            $result = (bool) array_shift(self::$registerHookResults);
        }

        if ($result) {
            self::$registeredHooks[] = $hook_name;
        }

        return $result;
    }

    protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        self::$transLog[] = array('id' => $id, 'domain' => $domain);

        return $id;
    }

    public function displayError($error)
    {
        $this->error = true;
        $output = '<div class="bootstrap"><div class="module_error alert alert-danger">';
        if (is_array($error)) {
            $output .= implode(' ', $error);
        } else {
            $output .= $error;
        }
        $output .= '</div></div>';

        return $output;
    }

    public function displayConfirmation($string)
    {
        return '<div class="bootstrap"><div class="module_confirmation conf confirm alert alert-success">' . $string . '</div></div>';
    }

    protected function getCacheId($name = null)
    {
        $cache_array = array();
        $cache_array[] = $name !== null ? $name : $this->name;
        if (Configuration::get('PS_SSL_ENABLED')) {
            $cache_array[] = (int) Tools::usingSecureMode();
        }
        if (Shop::isFeatureActive()) {
            $cache_array[] = (int) $this->context->shop->id;
        }
        if (Group::isFeatureActive() && isset($this->context->customer)) {
            $cache_array[] = (int) Group::getCurrent()->id;
            $cache_array[] = implode('_', Customer::getGroupsStatic($this->context->customer->id));
        }
        if (Language::isMultiLanguageActivated()) {
            $cache_array[] = (int) $this->context->language->id;
        }
        if (Currency::isMultiCurrencyActivated()) {
            $cache_array[] = (int) $this->context->currency->id;
        }
        $cache_array[] = (int) $this->context->country->id;

        return implode('|', $cache_array);
    }

    public function isCached($template, $cache_id = null, $compile_id = null)
    {
        $key = $template . "\0" . (string) $cache_id;
        if (!array_key_exists($key, self::$cachedMap)) {
            return false;
        }

        return (bool) self::$cachedMap[$key];
    }

    public function setCached($template, $cache_id, $cached)
    {
        $key = $template . "\0" . (string) $cache_id;
        self::$cachedMap[$key] = (bool) $cached;
    }

    public function fetch($templatePath, $cache_id = null, $compile_id = null)
    {
        self::$fetchCalls[] = array(
            'template' => $templatePath,
            'cache_id' => $cache_id,
            'compile_id' => $compile_id,
        );

        return 'rendered:' . $templatePath . '|' . $cache_id;
    }

    protected function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        self::$clearCacheCalls[] = array(
            'template' => $template,
            'cache_id' => $cache_id,
            'compile_id' => $compile_id,
        );

        return 1;
    }
}

}
