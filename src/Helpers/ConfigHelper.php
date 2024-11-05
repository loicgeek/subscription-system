<?php 
namespace NtechServices\SubscriptionSystem\Helpers;

use Illuminate\Database\Eloquent\Model;

class ConfigHelper
{
    public static $configPrefix = 'subscription';
    public static function get(string $key, $default = null)
    {
        return config(ConfigHelper::$configPrefix.".{$key}", $default);
    }

    public static function getConfigTable(string $key, $default = null)
    {

        return  ConfigHelper::get("tables.prefix")."".config(ConfigHelper::$configPrefix.".tables.{$key}", $default);
    }

    public static function getConfigClass(string $key, $default = null): Model|string
    {
        return config(ConfigHelper::$configPrefix.".models.{$key}", $default);
    }
}