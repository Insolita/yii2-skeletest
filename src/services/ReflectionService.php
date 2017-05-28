<?php
/**
 * Created by solly [27.05.17 23:14]
 */
declare(strict_types=1);

namespace insolita\skeletest\services;

/**
 * Class ReflectionService
 *
 * @package insolita\skeletest\services
 */
class ReflectionService
{
    const METHOD_CONSTRUCTOR = '__construct';
    
    /**
     * @var int
     */
    private $reflectFilter;
    
    /**
     * Reflector constructor.
     *
     * @param bool $withPrivate
     * @param bool $withProtected
     * @param bool $withStatic
     */
    public function __construct(bool $withPrivate, bool $withProtected, bool $withStatic)
    {
        $this->reflectFilter = $this->buildMethodFilter($withPrivate, $withProtected, $withStatic);
    }
    
    /**
     * @param \ReflectionClass $reflection
     *
     * @return array
     */
    public function extractMethods(\ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods($this->reflectFilter) as $method) {
            if (($method->isAbstract() && !$reflection->isInterface())
                || $method->isGenerator()
                || $method->isConstructor()
                || $method->isDestructor()
                || (trim($method->class, '\\') !== trim($reflection->getName(), '\\')) //Prevent catch inherited methods
            ) {
                continue;
            }
            $methodName = $method->getShortName();
            $methods[$methodName] = $this->buildMethodParams($method);
        }
        return $methods;
    }
    
    /**
     *  @param \ReflectionClass $reflection
     * @return array|null
     */
    public function extractConstructor(\ReflectionClass $reflection): ?array
    {
        $constructor = $reflection->getConstructor();
        if (is_null($constructor)) {
            $data = null;
        } else {
            $data = $this->buildMethodParams($constructor);
        }
        return $data;
    }
    
    /**
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    protected function buildMethodParams(\ReflectionMethod $method): array
    {
        $params = $definitions = $required = [];
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
                    if ($parameter->allowsNull()) {
                        $definition .= '=null';
                    } else {
                        $definition .= '=' . $parameter->getDefaultValue();
                    }
                }
                $params[$parameter->getPosition()] = '$' . $parameter->getName();
                if (!$parameter->isOptional()) {
                    $required[$parameter->getPosition()] = '$' . $parameter->getName();
                }
                $definitions[$parameter->getPosition()] = $definition;
            }
        }
        $paramString = implode(',', $definitions);
        if (strlen($paramString) > 50) {
            $paramString = PHP_EOL . implode(',' . PHP_EOL, $definitions);
        }
        return [
            'params'    => $params,
            'paramsig'  => implode(',', $params),
            'required'  => implode(',', $required),
            'signature' => $method->class . '::' . $method->getShortName() . '(' . $paramString . ')',
        ];
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
}
