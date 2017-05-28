<?php
/**
 * Created by solly [01.05.17 11:05]
 */

namespace insolita\skeletest;

/**
 * Class AccessibleMethodTrait
 *
 * @package insolita\skeletest
 */
/**
 * Trait AccessibleMethodTrait
 *
 * @package insolita\skeletest
 */
trait AccessibleMethodTrait
{
    /**
     * @param $object
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function callMethod($object, $method, $args)
    {
        $classReflection = new \ReflectionClass(get_class($object));
        $methodReflection = $classReflection->getMethod($method);
        $methodReflection->setAccessible(true);
        $result = $methodReflection->invokeArgs($object, $args);
        $methodReflection->setAccessible(false);
        return $result;
    }
    
    /**
     * @param $object
     * @param $property
     *
     * @return mixed
     */
    public function getProperty($object, $property)
    {
        $classReflection = new \ReflectionClass(get_class($object));
        $propReflection = $classReflection->getProperty($property);
        $propReflection->setAccessible(true);
        $result = $propReflection->getValue($object);
        $propReflection->setAccessible(false);
        return $result;
    }
    
    /**
     * @param $object
     * @param $property
     * @param $value
     */
    public function setProperty($object, $property, $value)
    {
        $classReflection = new \ReflectionClass(get_class($object));
        $propReflection = $classReflection->getProperty($property);
        $propReflection->setAccessible(true);
        $propReflection->setValue($object, $value);
        $propReflection->setAccessible(false);
    }
}
