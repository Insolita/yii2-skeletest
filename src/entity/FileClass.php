<?php
/**
 * Created by solly [27.05.17 23:01]
 */
declare(strict_types=1);

namespace insolita\skeletest\entity;

/**
 * Class FileClass
 *
 * @package insolita\skeletest\entity
 */
final class FileClass
{
    /**
     * Full file path
     * @var string
     */
    private $path;
    
    /**
     * File name without extension
     * @var string
     */
    private $name;
    
    /**
     * fileClass namespace
     * @var string
     */
    private $namespace;
    
    /**
     * FQN class name;
     * @var string
     */
    private $fqnClass;
    
    /**
     * FileClass constructor.
     *
     * @param string $path
     * @param string $name
     * @param string $namespace
     * @param string $fqnClass
     */
    public function __construct(string $path, string $name, string $namespace, string $fqnClass)
    {
        $this->path = $path;
        $this->name = $name;
        $this->namespace = $namespace;
        $this->fqnClass = $fqnClass;
    }
    
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }
    
    /**
     * @return string
     */
    public function getFqnClass(): string
    {
        return $this->fqnClass;
    }
    
    
}
