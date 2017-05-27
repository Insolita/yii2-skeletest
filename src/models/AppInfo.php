<?php
/**
 * Created by solly [30.04.17 12:26]
 */

namespace insolita\skeletest\models;

use yii\base\InvalidConfigException;
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
     * @param array $config
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['appPath'], $config['testPath'], $config['testNs'])) {
            throw new InvalidConfigException('Wrong pathMap configuration');
        }
        $this->appPath = FileHelper::normalizePath(\Yii::getAlias($config['appPath']));
        $this->testPath = FileHelper::normalizePath(\Yii::getAlias($config['testPath']));
        $this->testNs = $config['testNs'];
        if (isset($config['testerNs'])) {
            $this->testerNs = $config['testerNs'];
        }
    }
    
    /**
     * @return string
     */
    public function getAppPath()
    {
        return $this->appPath;
    }
    
    /**
     * @return string
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
     * @return string
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
