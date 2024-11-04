<?php 
namespace NtechServices\SubscriptionSystem\Config;

use Illuminate\Database\Eloquent\Model;

class ConfigHelper
{
    public $configPrefix = 'subscription';
    public static function get(string $key, $default = null)
    {
        return config(ConfigHelper::$configPrefix.".{$key}", $default);
    }

    public static function getConfigTable(string $key, $default = null)
    {
        return config(ConfigHelper::$configPrefix.".tables.{$key}", $default);
    }

    public static function getConfigClass(string $key, $default = null): Model
    {
        return config(ConfigHelper::$configPrefix.".models.{$key}", $default);
    }
}