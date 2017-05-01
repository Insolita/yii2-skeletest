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
}
