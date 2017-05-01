<?php
/**
 * Created by solly [29.04.17 21:32]
 */

namespace insolita\skeletest\models;

/**
 * Class Reflector
 *
 * @package insolita\skeletest\services
 */
final class Reflector
{
    private $reflectFilter;
    
    /**
     * Reflector constructor.
     *
     * @param string $className
     * @param int    $reflectFilter
     */
    public function __construct(bool $withPrivate, bool $withProtected, bool $withStatic)
    {
        $this->reflectFilter = $this->buildMethodFilter($withPrivate, $withProtected, $withStatic);
    }
    
    /**
     * @return array
     */
    public function extractMethods(string $className)
    {
        $methods = [];
        $reflection = new \ReflectionClass($className);
        foreach ($reflection->getMethods($this->reflectFilter) as $method) {
            if ($method->isAbstract()
                || $method->isGenerator()
                || $method->isConstructor()
                || $method->isDestructor()
                || (trim($method->class, '\\') !== trim($className, '\\')) //Prevent catch inherited methods
            ) {
                continue;
            }
            $methodName = $method->getShortName();
            $methods[$methodName] = $this->buildMethodSignature($method);
        }
        return $methods;
    }
    
    /**
     * @param bool $withPrivate
     * @param bool $withProtected
     * @param bool $withStatic
     *
     * @return int
     */
    private function buildMethodFilter(bool $withPrivate, bool $withProtected, bool $withStatic)
    {
        $filter = \ReflectionMethod::IS_PUBLIC;
        if ($withPrivate) {
            $filter |= \ReflectionMethod::IS_PRIVATE;
        }
        if ($withProtected) {
            $filter |= \ReflectionMethod::IS_PROTECTED;
        }
        if ($withStatic) {
            $filter |= \ReflectionMethod::IS_STATIC;
        }
        return $filter;
    }
    
    /**
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    private function buildMethodSignature(\ReflectionMethod $method)
    {
        $params = [];
        if (!empty($method->getParameters())) {
            foreach ($method->getParameters() as $parameter) {
                $definition = '$' . $parameter->getName();
                if ($parameter->isVariadic()) {
                    $definition = '...' . $definition;
                }
                if ($parameter->isPassedByReference()) {
                    $definition = '&' . $definition;
                }
                if ($parameter->hasType()) {
                    $definition = $parameter->getType() . ' ' . $definition;
                }
                if ($parameter->isDefaultValueAvailable()) {
                    if($parameter->allowsNull()){
                        $definition .= '=null';
                    }else{
                        $definition .= '=' . $parameter->getDefaultValue();
                    }
                }
                $params[$parameter->getPosition()] = $definition;
            }
        }
        $paramstr = implode(',', $params);
        if(strlen($paramstr) > 50){
            $paramstr = PHP_EOL.implode(','.PHP_EOL, $params);
        }
        return $method->class . '::' . $method->getShortName() . '(' . $paramstr. ')';
    }
}
