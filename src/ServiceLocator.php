<?php
namespace AliceDialogs;

use Psr\Container\ContainerInterface;

final class ServiceLocator /* implements ContainerInterface */{

    protected static $_serviceList = [];

    public static function set($id,$object)
    {
        self::$_serviceList[$id] = $object;
    }

    public static function get($id){
        return self::$_serviceList[$id];
    }

    public static function has($id){
        return key_exists($id, self::$_serviceList);
    } 
}
