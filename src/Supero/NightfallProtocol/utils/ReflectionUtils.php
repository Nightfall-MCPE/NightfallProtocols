<?php

namespace Supero\NightfallProtocol\utils;

use ReflectionClass;
use ReflectionException;

final class ReflectionUtils{
    private static $propCache = [];
    private static $methCache = [];

    /**
     * @param string $className
     * @param object $instance
     * @param string $propertyName
     * @param        $value
     *
     * @throws ReflectionException
     */
    public static function setProperty(string $className, object $instance, string $propertyName, $value) : void{
        if(!isset(self::$propCache[$k = "$className - $propertyName"])){
            $refClass = new ReflectionClass($className);
            $refProp = $refClass->getProperty($propertyName);
        }else{
            $refProp = self::$propCache[$k];
        }
        $refProp->setValue($instance, $value);
    }

    /**
     * @param string $className
     * @param object $instance
     * @param string $propertyName
     *
     * @return mixed
     * @throws ReflectionException
     */
    public static function getProperty(string $className, object $instance, string $propertyName) : mixed{
        if(!isset(self::$propCache[$k = "$className - $propertyName"])){
            $refClass = new ReflectionClass($className);
            $refProp = $refClass->getProperty($propertyName);
        }else{
            $refProp = self::$propCache[$k];
        }
        return $refProp->getValue($instance);
    }

    /**
     * @param string $className
     * @param object $instance
     * @param array $propertiesNames
     *
     * @return array
     * @throws ReflectionException
     */
    public static function getProperties(string $className, object $instance, array $propertiesNames) : array{
        $result = [];
        foreach($propertiesNames as $propertyName) $result[$propertyName] = self::getProperty($className, $instance, $propertyName);
        return $result;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param mixed  ...$args
     *
     * @return mixed
     * @throws ReflectionException
     */
    public static function invokeStatic(string $className, string $methodName, ...$args) : mixed{
        if(!isset(self::$methCache[$k = "$className - $methodName"])){
            $refClass = new ReflectionClass($className);
            $refMeth = $refClass->getMethod($methodName);
        }else{
            $refMeth = self::$methCache[$k];
        }
        return $refMeth->invoke(null, ...$args);
    }

    /**
     * @param string $className
     * @param object $instance
     * @param string $methodName
     * @param mixed  ...$args
     *
     * @return mixed
     * @throws ReflectionException
     */
    public static function invoke(string $className, object $instance, string $methodName, ...$args) : mixed{
        if(!isset(self::$methCache[$k = "$className - $methodName"])){
            $refClass = new ReflectionClass($className);
            $refMeth = $refClass->getMethod($methodName);
        }else{
            $refMeth = self::$methCache[$k];
        }
        return $refMeth->invoke($instance, ...$args);
    }
}