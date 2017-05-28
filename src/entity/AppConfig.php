<?php
/**
 * Created by solly [27.05.17 23:07]
 */
declare(strict_types=1);

namespace insolita\skeletest\entity;

/**
 * Class AppConfig
 *
 * @package insolita\skeletest\entity
 */
final class AppConfig
{
    /**
     * Base path for application
     * @var string
     */
    private $appPath;
    
    /**
     * Base path for test unit directory
     * @var string
     */
    private $testPath;
    
    /**
     * Base namespace for tests classes
     * @var string
     */
    private $testNs;
    
    /**
     * Base fqn  tester className (like tests\unit\UnitTester)
     * @var string
     */
    private $testerClass;
    
    /**
     * AppConfig constructor.
     *
     * @param string $appPath
     * @param string $testPath
     * @param string $testNs
     * @param string $testerClass
     */
    public function __construct(string $appPath, string $testPath, string $testNs, string $testerClass  = '')
    {
        $this->appPath = $appPath;
        $this->testPath = $testPath;
        $this->testNs = $testNs;
        $this->testerClass = $testerClass;
    }
    
    /**
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->appPath;
    }
    
    /**
     * @return string
     */
    public function getTestPath(): string
    {
        return $this->testPath;
    }
    
    /**
     * @return string
     */
    public function getTestNs(): string
    {
        return $this->testNs;
    }
    
    /**
     * @return string
     */
    public function getTesterClass(): string
    {
        return $this->testerClass;
    }
    
    
}
