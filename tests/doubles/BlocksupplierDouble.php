<?php

class BlocksupplierDouble extends Module
{
    public static $childUninstallCount = 0;

    public function uninstall()
    {
        ++self::$childUninstallCount;

        return true;
    }

    public static function resetCounters()
    {
        self::$childUninstallCount = 0;
        Module::$parentUninstallCount = 0;
    }
}
