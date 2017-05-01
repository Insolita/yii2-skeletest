<?php
/**
 * Created by solly [30.04.17 12:26]
 */

namespace insolita\skeletest\models;

use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * Class AppInfo
 *
 * @package insolita\skeletest\models
 */
final class AppInfo
{
    private $appPath;
    
    private $testPath;
    
    private $testNs;
    
    private $testerNs;
    
    /**
     * AppInfo constructor.
     *
     * @param string $projectRoot
     * @param array  $config
     */
    public function __construct(array $config = [])
    {
        $this->appPath = FileHelper::normalizePath(\Yii::getAlias($config['appPath']));
        $this->testPath = FileHelper::normalizePath(\Yii::getAlias($config['testPath']));
        $this->testNs = $config['testNs'];
        if (isset($config['testerNs'])) {
            $this->testerNs = $config['testerNs'];
        }
    }
    
    /**
     * @return mixed
     */
    public function getAppPath()
    {
        return $this->appPath;
    }
    
    /**
     * @return mixed
     */
    public function getTestPath()
    {
        return $this->testPath;
    }
    
    /**
     * @return mixed
     */
    public function getTestNs()
    {
        return $this->testNs;
    }
    
    /**
     * @return mixed
     */
    public function getTesterNs()
    {
        return $this->testerNs;
    }
    
    /**
     * @return null|string
     */
    public function getTesterBaseName()
    {
        return ($this->testerNs ? StringHelper::basename($this->testerNs) : null);
    }
}
